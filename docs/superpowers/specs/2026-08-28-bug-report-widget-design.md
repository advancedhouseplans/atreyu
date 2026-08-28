# Bug Report Widget & Reviewer App — Design

**Date:** 2026-08-28
**Status:** Approved for planning
**Owner:** Jamie

## 1. Overview

Build a lightweight bug / feature-request reporting system with three pieces:

1. A **Livewire + Flux reviewer app** (this repo, `Bugs`) that ingests, stores, and helps triage reports.
2. A **companion Laravel package** installed in each internal app (AHP, Takeoff) that (a) renders a signed widget config token in HTML, (b) captures per-request server-side context in cache, and (c) exposes a signed callback so the reviewer app can hydrate that context on demand.
3. A **drop-in vanilla JS widget** served by the reviewer app that mounts a floating report button in the corner of every page in the source apps, captures browser + network + client-event context, and submits reports.

The widget must remain usable when the host app is broken (500 pages, partial DOM, error states). Server-side Laravel context (route, auth user, exceptions, queries, models retrieved) is captured per HTTP request in the source app cache under a short-lived debug ID; the reviewer app fetches it back via a signed HTTPS callback after receiving a report.

## 2. Goals & non-goals

**Goals:**
- One-click reporting from anywhere inside AHP or Takeoff.
- Reports include enough browser + Laravel context to reproduce and fix bugs without follow-up questions.
- Reviewer app is usable by two seeded users (Jamie + Keith) with type/severity/status/assignee tracking and threaded notes per report.
- Widget survives host-app breakage: floating button + shadow-root isolation + rendered into published error views.

**Non-goals (this iteration):**
- Public / third-party reporting. Only source apps that ship the companion package can submit.
- Custom labels, kanban boards, workflow automation. Tracking stays at type + status + priority + assignee + notes.
- Real-time reviewer collaboration (websockets / Reverb).
- macOS native reviewer client with push notifications — deferred to v2 once the ingestion API is stable.
- Grouping / deduplication (Sentry-style stack signatures).

## 3. Topology

Three artifacts, one composer package, one JS bundle:

| Piece | Lives in | Delivered as |
|---|---|---|
| Reviewer app | this repo | Laravel 13 + Livewire 4 + Flux 2, deployed to Laravel Cloud |
| Companion package | `packages/bug-companion/` inside this repo | Composer path repo in dev, tagged VCS repo in prod |
| Widget JS | this repo, built by Vite | `public/widget.js`, served from the reviewer app's domain |

Two source apps in scope now: AHP and Takeoff. Both are Laravel.

## 4. Data model

**Sources are config, not a table.** Config file `config/bug-sources.php` in the reviewer app maps `source_slug => {name, endpoint, secret}`. Skipped: sources DB table. Add when >5 apps or per-app dashboards need in-UI editing.

**Tables:**

```
users                     — Fortify default; seeded with jamie + keith
reports
  id
  source_slug              — ahp | takeoff (from token)
  environment              — production | staging | local (from token)
  reporter_user_id         — nullable, source-app-side user id
  reporter_email
  reporter_name
  type                     — bug | feature | other
  severity                 — low | med | high | blocker
  status                   — new | in_progress | done | wontfix
  assignee_id              — nullable → users.id
  title
  description              — markdown-rendered on display
  url
  referrer                 — nullable
  app_version              — nullable, from source app <meta>
  user_agent               — raw string
  browser
  browser_version
  os
  os_version
  device
  screen_wh                — "1920x1080"
  viewport_wh              — "1440x900"
  dpr                      — decimal
  timezone
  locale
  laravel_context          — jsonb: array of hydrated per-request contexts, most recent first
  client_events            — jsonb: rolling client-side event buffer
  network_log              — jsonb: rolling fetch/XHR buffer with debug_id when present
  created_at, updated_at
report_notes
  id, report_id → reports.id, user_id → users.id, body, created_at
report_attachments
  id, report_id → reports.id, disk, path, mime, size, kind (auto_screenshot | user_upload), created_at
```

Indexes: `reports(status, created_at)`, `reports(source_slug)`, `reports(assignee_id)`, `report_notes(report_id)`, `report_attachments(report_id)`.

**Per-request context shape** (stored inside `laravel_context[]`, one entry per debug ID hop):

```json
{
  "debug_id": "01JX…",
  "request": { "method": "GET", "path": "/plans/123", "route_name": "plans.show", "route_action": "PlansController@show", "timestamp": "…" },
  "auth": { "guard": "web", "user_id": 42, "user_email": "…" },
  "models": [{ "class": "App\\Models\\Plan", "key": 123 }, …],
  "queries": [{ "sql": "…", "bindings": ["…truncated 200 chars…"], "time_ms": 4.1, "connection": "mysql" }, …],
  "exceptions": [{ "class": "…", "message": "…", "frames": ["file:line", …] }, …]
}
```

## 5. Auth model

| Surface | Mechanism |
|---|---|
| Widget → `POST /api/reports` (reviewer app) | Widget presents the signed config token in `Authorization: Bearer …`. Reviewer app decodes token, looks up source by `source_slug` in config, HMAC-SHA256 verifies with per-source secret. |
| Reviewer → source `/_bug-debug/{ids}` callback | Reviewer signs outbound request with per-source secret. Signature = HMAC-SHA256 of `{timestamp}.{body}` in `X-Bugs-Signature`; timestamp in `X-Bugs-Timestamp`. Source companion middleware verifies with `hash_equals` and rejects if `abs(now - timestamp) > 60s`. |
| Reviewer dashboard UI | Fortify session auth. Registration removed from `Features::…`. Seeded users only. |
| Report attachment retrieval | Auth-gated controller route in reviewer app that streams the file via `Storage::disk(...)->response(...)`. Not publicly reachable. |

**Widget config token payload** (base64url of JSON, HMAC appended):
```json
{ "source_slug": "ahp", "env": "production", "endpoint": "https://bugs.internal",
  "user_id": 42, "user_email": "…", "user_name": "…", "exp": <unix+15min> }
```

Fresh token per page render — server-side rendered by the `@bugWidget` directive.

**Rate limit:** 10 report submissions per minute per `(source_slug, reporter_email)`. Guards against runaway error loops turning the widget into a submission storm.

## 6. Companion Laravel package (`packages/bug-companion/`)

### Package structure
```
packages/bug-companion/
  composer.json                       (PSR-4 BugCompanion\ → src/)
  config/bug-companion.php
  routes/web.php                      (route: /_bug-debug/{ids})
  resources/views/widget.blade.php    (<script> tag emitting signed config)
  resources/views/errors/{500,503,404,419}.blade.php   (publishable overrides that include @bugWidget)
  src/
    BugCompanionServiceProvider.php
    Http/Middleware/CaptureContext.php
    Http/Middleware/VerifyBugsCallback.php
    Http/Controllers/DebugContextController.php
    Support/ContextBuffer.php
    Support/TokenSigner.php
```

### `CaptureContext` middleware
- Registered by the service provider onto the `web` group via `Route::pushMiddlewareToGroup`.
- Generates a UUID `debug_id` per request.
- Binds a request-scoped `ContextBuffer` in the container.
- Wires listeners for the lifetime of the request:
  - `Model::retrieved` → push `{class, key}` (bounded 200 entries).
  - `DB::listen` → push `{sql, bindings, time_ms, connection}`, with each binding truncated to 200 chars (bounded 100 entries).
  - `app(ExceptionHandler::class)->reportable(fn($e) => $buffer->addException($e))` — captures `class`, `message`, first 30 stack frames.
- On response terminate: stash the buffer into cache under key `bug-companion:ctx:{debug_id}` with configured TTL (default 3600s). Add `X-Debug-Id` response header.

### `VerifyBugsCallback` middleware
- Reads `X-Bugs-Signature` and `X-Bugs-Timestamp`.
- Computes expected HMAC-SHA256 of `{timestamp}.{body}` with configured shared secret.
- Rejects with 401 if `hash_equals` fails or timestamp is stale (>60s).

### `DebugContextController@show`
- Route: `GET /_bug-debug/{ids}` under `VerifyBugsCallback`.
- Accepts pipe-joined IDs (`{ids}` = `"01A|01B|01C"`).
- Returns JSON `{ "01A": {context…}, "01B": null, "01C": {context…} }`. Missing keys are `null` (cache expired).

### `@bugWidget` blade directive
- Registered by service provider.
- Renders `<script src="{config.endpoint}/widget.js" defer data-config="{signed_token}"></script>`.
- Fresh token per render, 15-minute expiry.
- No-op when `config('bug-companion.enabled')` is false.

### Config file (`bug-companion.php`)
```php
return [
    'source_slug' => env('BUG_COMPANION_SOURCE'),
    'endpoint'    => env('BUG_COMPANION_ENDPOINT'),
    'secret'      => env('BUG_COMPANION_SECRET'),
    'enabled'     => env('BUG_COMPANION_ENABLED', true),
    'context_ttl' => env('BUG_COMPANION_CTX_TTL', 3600),
    'max_queries' => 100,
    'max_models'  => 200,
];
```

### Publishable assets
- Config: `config/bug-companion.php`.
- Error view overrides: `resources/views/errors/{500,503,404,419}.blade.php`.
  Each extends the source app's default look but includes `@bugWidget` in the head, so reports can be filed from an error page. Publishing is opt-in.

### Install path in AHP / Takeoff
```
composer require ahp/bug-companion
php artisan vendor:publish --tag=bug-companion-config
php artisan vendor:publish --tag=bug-companion-errors   # optional
# add @bugWidget to app.blade.php <head>
# set BUG_COMPANION_SOURCE, BUG_COMPANION_ENDPOINT, BUG_COMPANION_SECRET in .env
```

## 7. Widget JS bundle

**Target:** single IIFE at `public/widget.js`, built by this repo's Vite. Only bundled runtime dep is `html2canvas`. No framework — vanilla JS. Isolated via shadow root.

### Init flow
1. `<script src="/widget.js" defer data-config="…">` — emitted by `@bugWidget`.
2. On `DOMContentLoaded`: read own `data-config`, base64-decode the payload half of the token (HMAC is not verified client-side — reviewer app is the only verifier), extract `endpoint`, `source_slug`, `env`, reporter identity.
3. If `document.body` missing (early error), use `MutationObserver` on `document.documentElement` to insert once body arrives.
4. Append `<div id="bug-widget-root">` with shadow root; mount floating button + hidden modal inside.
5. Register keyboard shortcut `Ctrl+Shift+B` as bonus opener (free — cheap listener). Not the primary UX.

### Instrumentation (starts on script load, before button mount)

| Signal | How | Buffer |
|---|---|---|
| Fetch calls | Wrap `window.fetch`. Record `{method, url, status, duration_ms, ts, debug_id}` (`debug_id` from `X-Debug-Id` response header when present). Skip calls to `endpoint`. | Rolling 50 |
| XHR calls | Wrap `XMLHttpRequest.prototype.open` + `send`. Same fields. | Same buffer |
| Unhandled errors | `window.addEventListener('error')` | Rolling 100 |
| Unhandled rejections | `window.addEventListener('unhandledrejection')` | Same buffer |
| `console.error` / `console.warn` | Wrap methods, forward to original. | Same buffer |
| Nav events | `pushState` / `popstate` / `hashchange` | Same buffer |

All buffers are ring-buffered — bounded memory.

### On submit, captured fresh
- `location.href`, `document.referrer`, `document.title`.
- `screen.{width,height}`, `window.inner{Width,Height}`, `devicePixelRatio`.
- `Intl.DateTimeFormat().resolvedOptions().timeZone`, `navigator.language`.
- `navigator.userAgent`, `navigator.userAgentData` (Client Hints) when available.
- App version from `<meta name="app-version" content="…">` if present.
- html2canvas snapshot of the current viewport, encoded via `canvas.toBlob('image/jpeg', 0.85)` (JPEG so quality knob applies; typical size <500KB, hard cap 2MB — if oversize, re-encode at 0.7).

### Widget UI (inside shadow root)
- Floating 40×40 button, `position: fixed`, bottom-right, `z-index: 2147483647`. Small bug icon.
- Click → modal:
  - Type dropdown (bug / feature / other).
  - Severity dropdown (low / med / high / blocker).
  - Title input.
  - Description textarea.
  - Snapshot preview (thumb).
  - Attachment upload (drag-drop + click).
  - Read-only "Reporting as: {name} <{email}>" so users see we know who they are.
  - Submit button.
- Escape closes. Backdrop click closes with confirm-if-dirty.

### Submission
- `POST {endpoint}/api/reports`, `multipart/form-data`.
- Text fields: `title`, `description`, `type`, `severity`.
- File fields: `snapshot` (auto JPEG), `attachments[]` (user uploads).
- JSON field `metadata`: `{ url, referrer, title, screen_wh, viewport_wh, dpr, timezone, locale, user_agent, ua_client_hints, app_version, network_log, client_events, debug_ids }`.
- Header: `Authorization: Bearer <signed_config_token>`.
- Success → thank-you card, auto-close after 3s. Failure → error text + retry button, form persists.

### Error page resilience
- Mounts on `DOMContentLoaded` — works even if host app JS never boots.
- Shadow root isolates styles both ways.
- `z-index: 2147483647` beats any host overlay.
- Caveat: if the source app throws before rendering `@bugWidget` in the HTML head, the widget can't load. Mitigation: companion package publishes overridden error views that include `@bugWidget` in the head.

### Dev preview
`resources/dev/widget-preview.html` — a plain HTML page for iterating on the widget without needing a full source app. Vite serves it. Also used as the Playwright smoke test target.

## 8. Reviewer UI

### Routes
```
/login             (Fortify default)
/                  → redirect /reports
/reports           App\Livewire\Reports\Index
/reports/{report}  App\Livewire\Reports\Show
/reports/{report}/attachments/{attachment}   Controller: auth check → stream file
```

### Fortify config
Drop `Features::registration()` and `Features::updateProfileInformation()` from `config/fortify.php`. Login-only.

### Seed
`DatabaseSeeder` creates `jamie@advancedhouseplans.com` and Keith's email. Passwords sourced from `BUGS_SEED_PASSWORD` env — no committed default.

### `Reports\Index` (Livewire + Flux)
- Flux table.
- Columns: created (relative), source badge, type icon, severity pill, status pill, title (link), reporter, assignee avatar.
- Filters via Flux dropdowns bound to `#[Url]` props: source, type, severity, status, assignee, "mine".
- Sort: created desc default, click column headers to change.
- Debounced search over title + description.
- Flux pagination.
- Empty state copy.

### `Reports\Show` (Livewire + Flux)
Stacked sections:
1. **Header strip.** Title (inline-editable), type + severity + status pills, assignee dropdown, source + env badges, reporter name/email.
2. **Meta card.** Parsed browser/OS/device (via `whichbrowser/parser` at ingest), screen/viewport/dpr, timezone/locale, URL, referrer, app version.
3. **Attachments.** Auto-snapshot rendered inline (click to lightbox). Uploaded files listed with mime/size, click to download via signed controller route.
4. **Timeline.** Interleaved list of `client_events` + `network_log` entries + `laravel_context` per-request hops, sorted by timestamp descending. Each network row expands to show the matched Laravel context (route, auth user, exceptions, query log with timings, models retrieved). Missing/expired context IDs render as "context expired".
5. **Notes thread.** List of `report_notes` with author avatar, timestamp, markdown-rendered body (`Str::markdown`). Add-note form at bottom, `Ctrl+Enter` to submit.

## 9. Ingestion pipeline

`App\Http\Controllers\Api\ReportsController@store`:

1. Verify `Authorization: Bearer <token>` HMAC using per-source secret looked up from config by the token's `source_slug`. Reject with 401 on failure. Reject with 401 if `exp` passed.
2. Enforce 10 submissions/min rate limit keyed by `source_slug + reporter_email`. Return 429 on exceed.
3. Validate multipart payload via `StoreReportRequest`.
4. Persist `Report` row + `ReportAttachment` rows. Save files to configured disk (`local` in dev, `s3` in prod) under `reports/{report_id}/…`.
5. **Inline callback** to source app: single `GET {source.endpoint}/_bug-debug/{piped_ids}` signed with HMAC. Parse response, store into `report.laravel_context`. Callback failure (network, 5xx, timeout 5s) does NOT fail the submission — context is stored as `[]` and the failure is logged.
6. Parse `user_agent` with `whichbrowser/parser`, populate `browser`, `browser_version`, `os`, `os_version`, `device`.
7. Return `201 { report_id }`.

`ponytail:` — inline callback. Move to a queued job if submission median exceeds 1s.

## 10. Testing strategy

Pest, feature-heavy per project rules.

**Reviewer app:**
- `ReportsControllerTest`
  - Valid signature persists Report + attachments.
  - Invalid signature → 401.
  - Expired token → 401.
  - Rate limit → 429 after 11th call.
  - Source callback fires with valid signature; response is stored on `laravel_context`.
  - Source callback failure (mocked network error) still returns 201; context stored as `[]`.
  - UA parsed into columns.
- `Reports\IndexTest` — filters, search, pagination.
- `Reports\ShowTest` — status/assignee mutation persists, note posts render, attachment stream requires auth.
- `AuthTest` — registration route 404s, seeded users log in, dashboard requires auth.

**Companion package:**
- `TokenSignerTest` — sign/verify round trip, expired token rejected, tampered token rejected.
- `CaptureContextTest` — `Model::retrieved` captured, DB queries captured with truncated bindings, exception captured via `reportable()` tap, cache written under `bug-companion:ctx:{id}`, response has `X-Debug-Id`.
- `VerifyBugsCallbackTest` — valid signature passes, tampered rejected, timestamp > 60s rejected.
- `DebugContextControllerTest` — returns cached hydration, returns `null` for missing IDs.

**Widget JS:**
- Vitest unit tests: ring buffer bounds, fetch wrapper preserves original response, error listener captures.
- One Playwright smoke test: load `widget-preview.html` → click floating button → fill form → assert `POST` fired with expected multipart shape (mock the endpoint with `page.route`).

## 11. Rollout

1. Reviewer app skeleton: migrations, models, Fortify locked, seeded users, `Reports\Index` + `Show` scaffolds.
2. `ReportsController@store` + HMAC middleware + rate limit + faked companion source in tests.
3. Companion package inside `packages/bug-companion/`, its own tests.
4. Widget JS build + preview page + Playwright smoke.
5. Local integration: run reviewer app on `bugs.test`, wire AHP via composer path repo, add `@bugWidget`, submit a report end-to-end, confirm callback hydrates context.
6. Deploy reviewer app to Laravel Cloud; set per-source `.env` secrets.
7. Tag companion `v0.1.0`, add VCS repo, `composer require` in AHP + Takeoff production.

### Environment matrix

| Var | Reviewer | AHP | Takeoff |
|---|---|---|---|
| `BUGS_SEED_PASSWORD` | ✅ | | |
| `BUGS_SOURCE_AHP_SECRET` | ✅ | matches `BUG_COMPANION_SECRET` there | |
| `BUGS_SOURCE_TAKEOFF_SECRET` | ✅ | | matches `BUG_COMPANION_SECRET` there |
| `BUG_COMPANION_SOURCE` | | `ahp` | `takeoff` |
| `BUG_COMPANION_ENDPOINT` | | `https://bugs.internal` | `https://bugs.internal` |
| `BUG_COMPANION_SECRET` | | ✅ | ✅ |

## 12. Deferred / future work

All get `ponytail:` comments in code so they surface via `/ponytail-debt`:

- **Sources table.** Add when >5 apps or per-app dashboards need in-UI editing.
- **Queue the source-app callback.** Move from inline to a queued job when submission median exceeds ~1s.
- **Redact query bindings.** Add a `redact_bindings` flag when PII in bindings becomes a concern.
- **Real-time notes (Reverb).** Add when reviewer collisions actually happen.
- **Attachment thumbnails.** Add when list view feels slow.
- **Dedup / grouping.** Sentry-style stack signatures. Add when duplicate reports become noisy.
- **Native macOS reviewer client with push notifications.** v2 client against the ingestion API (add a small `GET /api/reports` and a device-token registration endpoint at that time). Deferred until the API is stable and volume warrants it.
