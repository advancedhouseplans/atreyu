# Obsidian design system implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task by task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Obsidian Blade/Alpine component library as the Composer package `ahp/obsidian`, then move Atreyu off MaryUI and DaisyUI onto it.

**Architecture:**
- Anonymous Blade components registered under the `ob` prefix, styled with Tailwind v4 utilities that read CSS custom-property tokens.
- One Alpine plugin module for behaviour. Pure logic (theme resolution, layer stack, sheet threshold, dock visibility, fuzzy ranking, inline-edit state, shortcuts, toast queue) lives in small `lib/*.js` modules tested with Vitest.
- The app consumes the package through a path repository, like `ahp/bug-companion`.

**Tech stack:** PHP 8.4, Laravel 13, Livewire 4.4 (bundled Alpine with focus, anchor, collapse, intersect, persist, and sort), Blaze 1.0, Tailwind 4, Vite+ (`vp`), Pest 5 + Orchestra Testbench 11, Vitest via `vp test` (jsdom), blade-heroicons 2.

**Spec:** `docs/superpowers/specs/2026-10-20-obsidian-design-system-design.md`. Read it alongside this plan; token values come from it.

## Global constraints

- Package name `ahp/obsidian`, path `packages/obsidian`, PHP namespace `Obsidian\`, component prefix `<x-ob::…>`.
- No new npm dependencies. The only new Composer requirement is `blade-ui-kit/blade-heroicons` (already installed via Mary), required by the package.
- The package must not reference app models, routes, or enums. App-specific data reaches it only through props, slots, and Livewire method names.
- Breakpoints: dock and no sidebar below `lg` (1024px); sheets instead of popovers and modals below `sm` (640px); 44px minimum touch targets below `sm`.
- Motion: 120ms (fast), 200ms (base), 320ms (slow), easing `cubic-bezier(.2,.8,.2,1)`. Every animation degrades to an opacity fade under `prefers-reduced-motion: reduce`.
- Glass: ~72% (dark) / ~75% (light) surface with `blur(24px) saturate(1.4)`. Scrim: black 40% / 20% with `blur(8px)`. Solid fallback under `@supports not (backdrop-filter: blur(1px))` and `prefers-reduced-transparency: reduce`. A popover inside a sheet or modal uses solid `surface-3`.
- Copy is sentence case. Icon-only controls require `label`.
- PHP: run `vendor/bin/pint --dirty --format agent` after PHP edits. Use curly braces, explicit return types, and PHPDoc array shapes, per `CLAUDE.md`.
- Commit message trailer: `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

### Refinements to the spec, decided here

1. **Two `<html>` attributes.** `<html>` carries `data-appearance` (the user preference: `system|light|dark`) and `data-mode` (the resolved value: `light|dark`). The `dark:` variant keys off `[data-mode=dark]`. The spec had a single `data-mode` attribute, which can't express `system`.
2. **Accent text token is `accent-fg`**, matching the `fg` naming (utility `text-accent-fg`). Status colours follow suit: `danger-fg`, `danger-soft`, and so on.
3. **`inline-edit` calls an existing Livewire method** (`action="updateTitle"`) rather than `wire:model.blur`, because the show pages already expose `update*` methods. Errors are read from the error key given by `name`.
4. **`/api/v1/me` keeps a legacy `theme` key** (`'dark'` when appearance is Dark, otherwise `'light'`) next to the new `appearance` and `accent` keys, because the macOS client reads `theme`.
5. **The command palette's "recent" list** lives client-side in `localStorage` key `ob-command-recent` (at most 5 entries of `{label, href}`).

## Review focus

1. **Esc with stacked overlays.** With a dropdown open inside a modal, Esc closes only the topmost layer, and focus returns to that layer's trigger. Owned by Task 5 (`layers.test.js`).
2. **Inline-edit double submit.** Pressing Enter and then blurring (or blurring twice) saves exactly once. An unchanged value doesn't call the server. Esc restores the original. Owned by Task 7 (`inline-edit.test.js`).
3. **Shortcuts while typing.** `/` must not open the palette when focus is in an input, textarea, select, or contenteditable. ⌘K / Ctrl+K always opens it. Owned by Task 8 (`shortcuts.test.js`).
4. **Stale theme values.** Values from the old system (`synthwave` in `localStorage`, a legacy `mary-theme` key, an unknown server value) fall back to `system`/`iris` without flashing or throwing. Owned by Task 2 (`theme.test.js`) and Task 10 (migration mapping test).
5. **Long content at 375px.** A 200-character report title, a 40-character unbroken token, and a project name in a table row, kanban card, page header, and dock-adjacent toast must truncate or wrap without horizontal page scroll. Owned by Task 9 (showcase "stress" section) and checked in Task 16.

---

## File structure

```
packages/obsidian/
  composer.json  phpunit.xml  .gitignore
  src/ObsidianServiceProvider.php
  resources/css/obsidian.css
  resources/fonts/inter-var-latin.woff2
  resources/fonts/jetbrains-mono-var-latin.woff2
  resources/js/obsidian.js                 plugin entry: stores + Alpine.data registrations
  resources/js/lib/{theme,layers,sheet,dock,toasts,fuzzy,shortcuts,inline-edit}.js (+ .test.js each)
  resources/js/data/{dropdown,modal,sheet,toasts,dock,tooltip,combobox,command-palette,inline-edit}.js
  resources/views/components/
    icon, button, badge, avatar, avatar/stack, kbd, spinner, separator, tooltip,
    field, input, password, textarea, select, checkbox, switch, radio-group, segmented, combobox,
    card, section, empty-state, stat, page-header, app-shell,
    sidebar, sidebar/item, sidebar/group, dock, dock/item, dock/action,
    table, table/head, table/row, table/cell, property, inline-edit,
    kanban, kanban/column, kanban/card,
    dropdown, menu/item, menu/separator, menu/group, modal, sheet, toasts,
    command-palette, command-palette/group, command-palette/item,
    theme-script                          (all .blade.php)
  resources/views/showcase.blade.php
  tests/TestCase.php  tests/Pest.php  tests/Feature/*Test.php
```

App files touched are listed per task.

---

### Task 1: Package scaffold and `icon`

**Files:**
- Create: `packages/obsidian/composer.json`, `phpunit.xml`, `.gitignore` (`/vendor`, `composer.lock`)
- Create: `packages/obsidian/src/ObsidianServiceProvider.php`
- Create: `packages/obsidian/resources/views/components/icon.blade.php`
- Create: `packages/obsidian/resources/views/showcase.blade.php` (placeholder `<h1>Obsidian</h1>`; filled in by Task 9)
- Create: `packages/obsidian/tests/TestCase.php`, `tests/Pest.php`, `tests/Feature/ServiceProviderTest.php`, `tests/Feature/IconTest.php`
- Modify: `composer.json` (root): add path repository `{ "type": "path", "url": "packages/obsidian", "options": { "symlink": true } }` and `"ahp/obsidian": "@dev"` under `require`

**Interfaces:**
- Produces:
  - `Obsidian\ObsidianServiceProvider`
  - Blade prefix `ob` via `Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'ob')`
  - View namespace `obsidian::` for `showcase`
  - Route `GET /_obsidian` named `obsidian.showcase`, middleware `web`; it aborts 404 unless `app()->isLocal()`
- Produces: `<x-ob::icon name="inbox-stack" variant="outline|solid|mini|micro" />`
  - Renders `svg('heroicon-{o|s|m|c}-'.$name, $attributes->class('size-5 shrink-0')->get('class'))` with `aria-hidden="true"`
  - Default variant is `outline`

`composer.json` mirrors `packages/bug-companion/composer.json`, with these differences:
- `name` `ahp/obsidian`
- `require`: `php ^8.3`, `illuminate/support ^13.0`, `illuminate/view ^13.0`, `livewire/livewire ^4.0`, `blade-ui-kit/blade-heroicons ^2.7`
- psr-4 `Obsidian\\` → `src/`, tests → `Obsidian\\Tests\\`
- provider `Obsidian\\ObsidianServiceProvider`

`TestCase::getPackageProviders()` returns `[BladeIconsServiceProvider::class, BladeHeroiconsServiceProvider::class, LivewireServiceProvider::class, ObsidianServiceProvider::class]`.

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/ServiceProviderTest.php
it('hides the showcase outside the local environment', function () {
    $this->get('/_obsidian')->assertNotFound();
});

it('serves the showcase locally', function () {
    app()->detectEnvironment(fn () => 'local');
    $this->get('/_obsidian')->assertOk()->assertSee('Obsidian');
});

// tests/Feature/IconTest.php
it('renders an outline heroicon by default', function () {
    $this->blade('<x-ob::icon name="inbox" class="size-4" />')
        ->assertSee('<svg', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('size-4', false);
});

it('renders the solid variant', function () {
    expect((string) $this->blade('<x-ob::icon name="inbox" variant="solid" />'))
        ->toBe((string) $this->blade('@svg("heroicon-s-inbox", "size-5 shrink-0", ["aria-hidden" => "true"])'));
});
```

- [ ] **Step 2: Install and run. Expect failure.** Run `cd packages/obsidian && composer install && vendor/bin/pest`. Expected: FAIL, provider class not found.
- [ ] **Step 3: Implement the provider, the route, the `icon` component, and the placeholder showcase.**
- [ ] **Step 4: Run.** `vendor/bin/pest` → PASS. Then run `composer update ahp/obsidian` at the root and confirm that `php artisan about | grep -i obsidian` or `composer show ahp/obsidian` lists it.
- [ ] **Step 5: Commit.** `git add packages/obsidian composer.json composer.lock && git commit -m "feat(obsidian): scaffold package with icon component"`

---

### Task 2: Tokens, fonts, theme script, host wiring

**Files:**
- Create: `packages/obsidian/resources/css/obsidian.css`
- Create: `packages/obsidian/resources/fonts/inter-var-latin.woff2`, `jetbrains-mono-var-latin.woff2`
- Create: `packages/obsidian/resources/js/obsidian.js`, `resources/js/lib/theme.js`, `resources/js/lib/theme.test.js`
- Create: `packages/obsidian/resources/views/components/theme-script.blade.php`, `tests/Feature/ThemeScriptTest.php`
- Modify: `resources/css/app.css` (import `obsidian.css` after `tailwindcss`; delete the `@custom-variant dark` line; DaisyUI stays until Task 16)
- Modify: `resources/js/app.js` (register the plugin)
- Modify: `vitest.config.js` (`include` adds `'packages/obsidian/resources/js/**/*.test.js'`)

**Fonts:** these are file downloads, so **ask the user before fetching**. Proposed sources (latin subset, variable weight):
- `https://cdn.jsdelivr.net/fontsource/fonts/inter:vf@latest/latin-wght-normal.woff2`
- `https://cdn.jsdelivr.net/fontsource/fonts/jetbrains-mono:vf@latest/latin-wght-normal.woff2`

Both are SIL OFL; add an `OFL.txt` next to them. Download into a new empty scratch directory first, check that `file` reports "Web Open Font Format (Version 2)", then move them in.

**`obsidian.css` contents:**
- `@custom-variant dark (&:where([data-mode=dark], [data-mode=dark] *));`
- `@source '../views';`
- `@font-face` for `Inter` and `JetBrains Mono` (`font-display: swap`, `font-weight: 100 900`)
- `@theme`:
  - colours: `--color-canvas`, `--color-surface-1..3`, `--color-hairline`, `--color-hairline-strong`, `--color-fg`, `--color-fg-2`, `--color-fg-3`, `--color-accent`, `--color-accent-hover`, `--color-accent-soft`, `--color-accent-fg`, `--color-on-accent`, and `--color-{danger,warning,success,info,neutral}` with `-soft` and `-fg` for each. Each maps to a `--ob-*` runtime variable.
  - fonts: `--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif` and `--font-mono: 'JetBrains Mono', ui-monospace, monospace`
  - easing: `--ease-ob: cubic-bezier(.2,.8,.2,1)`
- `:root` (light) and `[data-mode=dark]` blocks set `--ob-*` values from the spec's surfaces table.
- Status base colours:

  | Status | Base | Dark `-fg` | Light `-fg` |
  |---|---|---|---|
  | danger | `#F05252` | `#FF8F8F` | `#C81E1E` |
  | warning | `#F5B445` | `#F5C76E` | `#A15C07` |
  | success | `#22C55E` | `#4ADE80` | `#15803D` |
  | info | `#3B82F6` | `#7EB0FF` | `#1D5FD6` |

  Every `-soft` is `color-mix(in srgb, <base> 14%, transparent)`. `neutral-soft` = `hairline-strong`, `neutral-fg` = `fg-2`.
- `[data-accent=…]` blocks: `--ob-accent` is the spec's base hue.

  | Accent | Dark `accent-fg` | Light `accent-fg` |
  |---|---|---|
  | iris | `#A99FFF` | `#5B4BE0` |
  | blue | `#7EB0FF` | `#1D5FD6` |
  | teal | `#3ED6C2` | `#0F766E` |
  | green | `#4ADE80` | `#15803D` |
  | amber | `#FBBF24` | `#B45309` |
  | rose | `#FB7185` | `#BE123C` |

  - `on-accent`: `#FFFFFF` for iris, blue, and rose; `#0A0A0B` for teal, green, and amber.
  - `accent-hover`: `color-mix(in oklab, var(--ob-accent), white 10%)` in dark mode, `black 10%` in light.
  - `accent-soft`: `color-mix(in srgb, var(--ob-accent) 14%, transparent)`.
  - `:root` falls back to the iris values.
- `@utility glass` (the spec's glass values, inner highlight, shadow, and the two fallbacks); `@utility glass-solid` (`surface-3`, used inside sheets and modals); `@utility scrim`; `@utility focus-ring` (`:focus-visible` gets a 1px `accent` ring plus a 4px `accent-soft` halo).
- `@layer base`:
  - `html { color-scheme }` per mode; `body` gets `bg-canvas text-fg font-sans antialiased text-sm`
  - `::selection` uses `accent-soft`
  - thin scrollbars
  - `[x-cloak]{display:none!important}`
  - a reduced-motion block that sets `transition-property: opacity` and drops transforms on `[data-ob-motion]` elements

**Interfaces:**
- Produces: `lib/theme.js`
  - `export const APPEARANCES = ['system','light','dark']`
  - `export const ACCENTS = ['iris','blue','teal','green','amber','rose']`
  - `export function resolveTheme({ server, stored, prefersDark }): { appearance, accent, mode }`
    - `server` and `stored` are `{appearance?, accent?}`. The server value wins; invalid values fall through.
    - Defaults are `system` and `iris`. `mode` is `dark` when appearance is `dark`, or when it's `system` and `prefersDark`.
  - `export function applyTheme(root: HTMLElement, theme): void` sets `data-appearance`, `data-mode`, `data-accent`
- Produces: `obsidian.js`
  - `export default function obsidian(Alpine)` registers `Alpine.store('obTheme', { appearance, accent, mode, apply({appearance, accent}) })`
  - `apply` writes `localStorage['ob-theme'] = JSON.stringify({appearance, accent})` inside try/catch, calls `applyTheme`, and re-resolves on `matchMedia('(prefers-color-scheme: dark)')` change
  - It listens for `window` event `ob-theme-apply` (`detail: {appearance, accent}`)
  - Later tasks add their `Alpine.data` registrations to this file
- Produces: `<x-ob::theme-script :appearance="?string" :accent="?string" />`
  - Inline `<script>` that runs the same resolution as `resolveTheme` (re-implemented in ~12 lines of plain JS, since it must run before Vite) with `server` taken from the props via `@js`
  - It reads only `localStorage['ob-theme']` and removes the legacy keys `mary-theme` and `atreyu-theme`
- Host `app.js`:

  ```js
  import obsidian from '../../vendor/ahp/obsidian/resources/js/obsidian.js';
  document.addEventListener('alpine:init', () => window.Alpine.plugin(obsidian));
  ```

- [ ] **Step 1: Write the failing JS tests** (`lib/theme.test.js`)

```js
test('server preference wins', () => expect(resolveTheme({ server: { appearance: 'dark', accent: 'teal' }, stored: { appearance: 'light', accent: 'rose' }, prefersDark: false })).toEqual({ appearance: 'dark', accent: 'teal', mode: 'dark' }));
test('system follows the OS', () => expect(resolveTheme({ server: {}, stored: {}, prefersDark: true }).mode).toBe('dark'));
test('legacy and unknown values fall back to defaults', () => expect(resolveTheme({ server: { appearance: 'synthwave' }, stored: { appearance: 'night', accent: 'puce' }, prefersDark: false })).toEqual({ appearance: 'system', accent: 'iris', mode: 'light' }));
test('applyTheme sets all three attributes', () => { applyTheme(document.documentElement, { appearance: 'system', accent: 'amber', mode: 'dark' }); expect(document.documentElement.dataset).toMatchObject({ appearance: 'system', mode: 'dark', accent: 'amber' }); });
```

- [ ] **Step 2: Write the failing PHP test** (`ThemeScriptTest.php`)

```php
it('embeds the server preference and clears legacy keys', function () {
    $this->blade('<x-ob::theme-script appearance="dark" accent="teal" />')
        ->assertSee("'dark'", false)->assertSee("'teal'", false)
        ->assertSee('ob-theme', false)->assertSee('mary-theme', false);
});

it('renders with no preference', function () {
    $this->blade('<x-ob::theme-script />')->assertSee('<script>', false);
});
```

- [ ] **Step 3: Run both. Expect failure.** Run `npx vp test --run packages/obsidian` and `cd packages/obsidian && vendor/bin/pest --filter=ThemeScript`. Expected: FAIL.
- [ ] **Step 4: Implement** `obsidian.css`, the fonts (after the user approves the download), `lib/theme.js`, `obsidian.js`, `theme-script`, and the host wiring.
- [ ] **Step 5: Run.** Both suites PASS. `npm run build` succeeds, and the built CSS contains `--color-canvas` and an `@font-face` for Inter (check with `grep -c "color-canvas" public/build/assets/app-*.css`).
- [ ] **Step 6: Commit.** `feat(obsidian): design tokens, fonts, theme resolution`

---

### Task 3: Primitives

**Files:**
- Create components: `button`, `badge`, `avatar`, `avatar/stack`, `kbd`, `spinner`, `separator`, `tooltip` (all `.blade.php`)
- Create: `resources/js/data/tooltip.js`
- Test: `tests/Feature/ButtonTest.php`, `tests/Feature/PrimitivesTest.php`
- Modify: `obsidian.js` (register `obTooltip`)

**Interfaces:**
- `<x-ob::button>`
  - Props: `variant='secondary'` (`primary|secondary|ghost|danger`), `size='md'` (`sm|md|lg`), `icon=null`, `icon:trailing=null` (read as `$attributes->get('icon:trailing')`), `href=null`, `navigate=false`, `loading=null`, `label=null`, `type='button'`
  - Base classes: `inline-flex items-center justify-center gap-2 rounded-full font-medium transition duration-120 ease-ob active:scale-[.98] focus-ring disabled:opacity-50`
  - Variant classes:

    | Variant | Classes |
    |---|---|
    | primary | `bg-accent text-on-accent hover:bg-accent-hover` |
    | secondary | `bg-surface-1 text-fg border border-hairline-strong hover:bg-surface-3` |
    | ghost | `text-fg-2 hover:bg-surface-3 hover:text-fg` |
    | danger | `bg-danger-soft text-danger-fg hover:bg-danger hover:text-white` |

  - Size classes:

    | Size | Classes |
    |---|---|
    | sm | `h-8 px-3 text-[13px]` |
    | md | `h-9 px-4 text-sm max-sm:h-11` |
    | lg | `h-11 px-5 text-[15px]` |

  - Icon-only (no slot content): square `size-8` / `size-9 max-sm:size-11` / `size-11`, `aria-label=$label`, wrapped in a tooltip. If `label` is missing it throws `InvalidArgumentException('Icon-only ob::button requires a label.')`.
  - `href` renders `<a>`; `navigate` adds `wire:navigate`.
  - When `wire:click` is present and `loading !== false`, it adds `wire:loading.attr="data-loading"` and `wire:target="{method}"`, where method is the part of the `wire:click` value before `(`. It also renders a `spinner` shown via `in-data-loading:inline-flex`, hiding the icon.
  - Add `@blaze` at the top, unless the Step 4 render tests fail under it; in that case drop it and note why in the commit message.
- `<x-ob::badge color='neutral' dot=false>`
  - `color`: `neutral|danger|warning|success|info|accent`
  - Classes `inline-flex items-center gap-1.5 rounded-full px-2 h-5 text-xs font-medium bg-{color}-soft text-{color}-fg`; the accent version uses `bg-accent-soft text-accent-fg`
  - `@blaze`
- `<x-ob::avatar src=null initials=null size='md'>`
  - Sizes: `sm` 24px, `md` 32px, `lg` 40px
  - Without `src`, initials on `bg-surface-3 text-fg-2`
  - `@blaze`
- `<x-ob::avatar.stack>` overlaps its children by `-space-x-2` with a `ring-2 ring-canvas` on each.
- `<x-ob::kbd>` (mono 11px, `border-hairline-strong`), `<x-ob::spinner>` (`size-4`, `motion-safe:animate-spin`), `<x-ob::separator vertical=false label=null>`. All `@blaze`.
- `<x-ob::tooltip text="…" position="top">`
  - Wraps its slot. Uses `x-anchor`, with `glass` and `rounded-lg text-xs` on the panel.
  - Opens after 400ms on hover or focus and closes on leave or Esc.
  - `obTooltip` skips opening when `matchMedia('(hover: none)').matches`.

- [ ] **Step 1: Write the failing tests** (`ButtonTest.php`)

```php
it('applies variant and size classes', fn () => $this->blade('<x-ob::button variant="primary" size="sm">Save</x-ob::button>')->assertSee('bg-accent', false)->assertSee('h-8', false)->assertSee('type="button"', false));
it('renders a link for href and adds wire:navigate', fn () => $this->blade('<x-ob::button href="/reports" navigate>Reports</x-ob::button>')->assertSee('<a', false)->assertSee('href="/reports"', false)->assertSee('wire:navigate', false));
it('scopes the loading state to its own action', fn () => $this->blade('<x-ob::button wire:click="save(1)">Save</x-ob::button>')->assertSee('wire:target="save"', false));
it('requires a label when icon-only', fn () => $this->blade('<x-ob::button icon="trash" />'))->throws(ViewException::class, 'requires a label');
it('labels icon-only buttons', fn () => $this->blade('<x-ob::button icon="trash" label="Delete" />')->assertSee('aria-label="Delete"', false));
```

`PrimitivesTest.php` covers `badge color="danger" dot` (contains `bg-danger-soft` and a dot span), `avatar initials="JH"` (shows `JH`, no `<img`), and `tooltip text="Hi"` (contains `role="tooltip"`).

- [ ] **Step 2: Run.** `vendor/bin/pest --filter='Button|Primitives'` → FAIL.
- [ ] **Step 3: Implement the components and `data/tooltip.js`.**
- [ ] **Step 4: Run.** PASS.
- [ ] **Step 5: Commit.** `feat(obsidian): primitives`

---

### Task 4: Forms

**Files:**
- Create components: `field`, `input`, `password`, `textarea`, `select`, `checkbox`, `switch`, `radio-group`, `segmented`
- Test: `tests/Feature/FormsTest.php`

**Interfaces:**
- **Shared rule:** each control computes `$model = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('x-model')` and `$errorKey = $attributes->get('name') ?? $attributes->wire('model')->value()`.
  - The control element receives `$attributes->except(['class','label','hint'])`. The wrapper receives `class`.
  - Only `field` reads `$errors`. Field-based controls do **not** use `@blaze`.
- `<x-ob::field label=null hint=null :error-key=null for=null>`
  - Renders `<label for>`, the slot, then the hint (`text-xs text-fg-3`) or the first error for `error-key` (`text-xs text-danger-fg`, `role="alert"`).
  - On error, it sets `data-invalid` on the wrapper; controls style `in-data-invalid:border-danger`.
- `<x-ob::input label hint icon=null icon:trailing=null clearable=false prefix=null suffix=null type='text'>`
  - Input classes: `h-9 max-sm:h-11 w-full rounded-lg bg-surface-1 border border-hairline px-3 text-sm placeholder:text-fg-3 focus-ring`
  - `clearable` adds an icon button that sets the value to `''` and dispatches `input`.
  - `id` defaults to the error key or `Str::random`.
- `<x-ob::password>` is an `input` plus a reveal toggle (Alpine `show`, `label="Show password"`/`"Hide password"`).
- `<x-ob::textarea rows=3>` auto-grows with Alpine (`$el.style.height = $el.scrollHeight+'px'` on input).
- `<x-ob::select :options=[] placeholder=null>`
  - `options` is `list<array{value: string|int, label: string}>`, or a slot of `<option>`.
  - Native `<select>` with an appearance-none chevron icon.
- `<x-ob::checkbox label>`, `<x-ob::switch label>` (`role="switch"`, `aria-checked` bound via Alpine to the checkbox state), `<x-ob::radio-group :options label>`.
- `<x-ob::segmented :options name>`
  - `options` is `list<array{value, label, icon?}>`.
  - Renders a `role="radiogroup"` pill track `bg-surface-1 border-hairline p-0.5` with an Alpine-positioned sliding indicator `bg-surface-3` (`transition-[left,width] duration-200 ease-ob`) under a radio input per option.

- [ ] **Step 1: Write the failing tests** (`FormsTest.php`). Use a helper that shares errors: `view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['email' => ['Enter an email address.']])))`.

```php
it('shows the error for its wire:model name', fn () => withErrors()->blade('<x-ob::input label="Email" wire:model="email" />')->assertSee('Enter an email address.')->assertSee('data-invalid', false)->assertSee('role="alert"', false));
it('forwards wire:model to the inner control', fn () => $this->blade('<x-ob::input wire:model.live="q" class="w-64" />')->assertSeeInOrder(['class="w-64', '<input', 'wire:model.live="q"'], false));
it('links the label to the control', fn () => $this->blade('<x-ob::textarea label="Notes" wire:model="notes" />')->assertSee('for="notes"', false)->assertSee('id="notes"', false));
it('renders select options', fn () => $this->blade('<x-ob::select :options="[[\'value\' => \'new\', \'label\' => \'New\']]" placeholder="Any status" />')->assertSee('<option value="">Any status</option>', false)->assertSee('<option value="new">New</option>', false));
it('marks switches with the switch role', fn () => $this->blade('<x-ob::switch label="Notify" wire:model="notify" />')->assertSee('role="switch"', false));
it('renders a segmented control as a radiogroup', fn () => $this->blade('<x-ob::segmented name="mode" :options="[[\'value\' => \'dark\', \'label\' => \'Dark\']]" />')->assertSee('role="radiogroup"', false)->assertSee('value="dark"', false));
```

- [ ] **Step 2: Run.** `vendor/bin/pest --filter=Forms` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run.** PASS.
- [ ] **Step 5: Commit.** `feat(obsidian): form controls`

---

### Task 5: Overlays and toasts

**Files:**
- Create components: `dropdown`, `menu/item`, `menu/separator`, `menu/group`, `modal`, `sheet`, `toasts`
- Create: `resources/js/lib/layers.js`, `lib/sheet.js`, `lib/toasts.js` (each with `.test.js`)
- Create: `resources/js/data/{dropdown,modal,sheet,toasts}.js`
- Test: `tests/Feature/OverlaysTest.php`
- Modify: `obsidian.js` (register stores and data)

**Interfaces:**
- `lib/layers.js`: `export function createLayerStack()` returns `{ push(id, close: () => void), remove(id), top(): string|null, escape(): void }`.
  - `escape` calls only the top layer's `close` and removes it.
  - It's registered as `Alpine.store('obLayers', createLayerStack())`, with a single `window` keydown Esc listener calling `escape()`.
  - Overlays must not bind their own Esc handlers.
- `lib/sheet.js`: `export function shouldDismiss({ distance, height, velocity }): boolean` returns `distance > 0 && (distance / height >= 0.3 || velocity > 0.5)` (px/ms, downward positive).
- `lib/toasts.js`: `export function createToastQueue({ max = 3, duration = 4000, setTimer = setTimeout, clearTimer = clearTimeout })` returns `{ items, push({message, variant='neutral', action=null}): id, dismiss(id), pause(id), resume(id) }`.
  - Pushing beyond `max` drops the oldest.
  - `resume` restarts the remaining time.
- `<x-ob::dropdown position='bottom-end'>` with a `trigger` slot.
  - At `sm` and up: `x-anchor.{position}.offset.6` panel, `glass rounded-xl p-1 min-w-48`, enter `opacity-0 scale-[.96]` → `opacity-100 scale-100` over 200ms.
  - Below `sm`: the same panel renders as a bottom sheet (`obSheet` behaviour) with a scrim.
  - Uses `role="menu"`, arrow-key navigation over `[role=menuitem]`, typeahead on first letter, and returns focus to the trigger on close.
  - Inside an element with `[data-ob-layer=modal|sheet]` the panel uses `glass-solid`.
- `<x-ob::menu.item icon=null href=null navigate=false variant=null shortcut=null>`
  - `role="menuitem"`, `h-9 max-sm:h-11 rounded-lg px-2.5`, hover `bg-surface-3`.
  - `variant="danger"` uses `text-danger-fg`.
  - Clicking closes the menu.
- `<x-ob::modal name=null title=null description=null size='md'>` with an optional `footer` slot.
  - Open state: if `wire:model` is present, `$wire.$entangle('<prop>')`. Otherwise `name` plus `window` events `modal-open` / `modal-close` with `detail.name`.
  - Size widths: `sm` `max-w-sm`, `md` `max-w-lg`, `lg` `max-w-2xl`.
  - Uses `role="dialog"`, `aria-modal="true"`, `aria-labelledby`, `x-trap.inert.noscroll`, `data-ob-layer="modal"`, and a `scrim` backdrop (click closes).
  - At `sm` and up: centered `glass rounded-xl`, scale and fade 320ms. Below `sm`: a bottom sheet.
- `<x-ob::sheet name=null side='bottom'>`
  - Opens the same way as modal. `data-ob-layer="sheet"`; `rounded-t-[20px]` (bottom) or a full-height right panel.
  - Has a drag handle. Pointer drag on the handle and header translates Y; on release, `shouldDismiss` decides close or snap back.
  - Respects `env(safe-area-inset-bottom)`.
- `<x-ob::toasts />`
  - Mounted once per layout. `aria-live="polite"`, `role="region"`, `aria-label="Notifications"`.
  - Position: bottom-right at `lg` and up; bottom-center above the dock (`bottom-[calc(5rem+env(safe-area-inset-bottom))]`) below `lg`.
  - Listens for `window` event `toast` (detail `{message, variant?, action?: {label, href}}`). Livewire triggers it with `$this->dispatch('toast', message: '…', variant: 'success')`.
  - Each toast is `glass`, and hover pauses its timer.

- [ ] **Step 1: Write the failing JS tests**

```js
// layers.test.js
test('escape closes only the top layer', () => { const s = createLayerStack(); const a = vi.fn(), b = vi.fn(); s.push('modal', a); s.push('menu', b); s.escape(); expect(b).toHaveBeenCalledOnce(); expect(a).not.toHaveBeenCalled(); expect(s.top()).toBe('modal'); });
test('escape on an empty stack is a no-op', () => expect(() => createLayerStack().escape()).not.toThrow());
// sheet.test.js
test.each([[100, 400, 0, false], [120, 400, 0, true], [20, 400, 0.6, true], [-50, 400, 2, false]])('shouldDismiss(%i, %i, %f) is %s', (distance, height, velocity, expected) => expect(shouldDismiss({ distance, height, velocity })).toBe(expected));
// toasts.test.js (vi.useFakeTimers())
test('auto-dismisses after 4s', () => { const q = createToastQueue(); q.push({ message: 'Saved' }); vi.advanceTimersByTime(3999); expect(q.items).toHaveLength(1); vi.advanceTimersByTime(1); expect(q.items).toHaveLength(0); });
test('keeps at most three', () => { const q = createToastQueue(); ['a','b','c','d'].forEach(m => q.push({ message: m })); expect(q.items.map(t => t.message)).toEqual(['b','c','d']); });
test('pause holds the timer', () => { const q = createToastQueue(); const id = q.push({ message: 'x' }); vi.advanceTimersByTime(1000); q.pause(id); vi.advanceTimersByTime(10000); expect(q.items).toHaveLength(1); q.resume(id); vi.advanceTimersByTime(3000); expect(q.items).toHaveLength(0); });
```

- [ ] **Step 2: Write the failing PHP tests** (`OverlaysTest.php`): the modal has `role="dialog"`, `aria-modal="true"`, and `x-trap`; a modal with `wire:model="showCreate"` contains `$entangle('showCreate')`; `menu.item variant="danger"` has `role="menuitem"` and `text-danger-fg`; `toasts` has `aria-live="polite"`.
- [ ] **Step 3: Run.** `npx vp test --run packages/obsidian` and `vendor/bin/pest --filter=Overlays` → FAIL.
- [ ] **Step 4: Implement.**
- [ ] **Step 5: Run.** PASS.
- [ ] **Step 6: Commit.** `feat(obsidian): dropdown, modal, sheet, toasts`

---

### Task 6: Layout shell

**Files:**
- Create components: `app-shell`, `sidebar`, `sidebar/item`, `sidebar/group`, `dock`, `dock/item`, `dock/action`, `page-header`, `card`, `section`, `empty-state`, `stat`
- Create: `resources/js/lib/dock.js`, `lib/dock.test.js`, `data/dock.js`
- Test: `tests/Feature/LayoutTest.php`

**Interfaces:**
- `lib/dock.js`: `export function nextDockVisibility({ previousY, y, visible, threshold = 8 }): boolean`
  - Returns `true` when `y <= 0`.
  - Hides when scrolling down by more than `threshold`, shows when scrolling up by more than `threshold`, and otherwise returns `visible`.
- `<x-ob::app-shell>` with slots `sidebar`, `dock`, and default.
  - Grid: `lg:grid-cols-[auto_1fr]`.
  - The sidebar is shown only at `lg` and up. The dock is shown only below `lg`.
  - `main` gets `pb-[calc(6rem+env(safe-area-inset-bottom))] lg:pb-0`.
  - Collapse state uses `x-data="{ collapsed: $persist(false).as('ob-sidebar-collapsed') }"`, exposed to children.
- `<x-ob::sidebar>`
  - `w-60` expanded / `w-16` collapsed (`transition-[width] duration-200`), `border-r border-hairline`, `bg-canvas`.
  - Slots: `brand`, default, `footer`. The collapse toggle is a ghost icon button labelled "Collapse sidebar"/"Expand sidebar".
- `<x-ob::sidebar.item href icon label badge=null active=null>`
  - `active` defaults to `request()->url() === $href || request()->is(trim(parse_url($href, PHP_URL_PATH), '/').'/*')`.
  - Active state: `aria-current="page"`, `bg-surface-3 text-fg`, and the icon gets `text-accent-fg`.
  - Always `aria-label=$label` and `wire:navigate`. When collapsed it shows only the icon, with a tooltip.
- `<x-ob::sidebar.group heading>`
- `<x-ob::dock>`
  - Fixed `inset-x-3.5 bottom-[calc(.875rem+env(safe-area-inset-bottom))] h-14 rounded-[20px] glass`, laid out `flex justify-around`.
  - `obDock` toggles a `translate-y-[150%]` class using `nextDockVisibility` on a passive scroll listener, with a 320ms transition.
- `<x-ob::dock.item href icon label active=null>` uses the same active rule; icon `size-6`, 44px hit area, `aria-current`.
- `<x-ob::dock.action icon label>` is a `button`, `size-11 rounded-[14px] bg-accent text-on-accent`; attributes are forwarded (for example `wire:click`, `x-on:click`).
- `<x-ob::page-header title description=null>` with an `actions` slot.
  - Desktop: `text-2xl font-semibold tracking-[-0.02em]`.
  - Below `sm`, a sticky `glass` compact bar (`h-12`, title `text-base`) appears once the large title leaves the viewport (`x-intersect:leave`).
- `<x-ob::card padding=true>`: `rounded-xl bg-surface-1 border border-hairline`, plus a light-mode `shadow-[0_1px_2px_rgb(0_0_0/.04),0_4px_12px_rgb(0_0_0/.04)]`.
- `<x-ob::section heading description>` with an `actions` slot.
- `<x-ob::empty-state icon heading>` with a body slot and an `action` slot.
- `<x-ob::stat label value icon=null selected=false>`
  - A `button`; forwards attributes.
  - Selected: `border-accent bg-accent-soft`, plus `aria-pressed`.
  - The value uses `text-3xl font-semibold tabular-nums tracking-[-0.04em]`.

- [ ] **Step 1: Write the failing JS tests** (`dock.test.js`)

```js
test.each([[100, 120, true, false], [120, 110, false, true], [100, 104, true, true], [100, 104, false, false], [50, 0, false, true]])('prev %i → %i (visible %s) gives %s', (previousY, y, visible, expected) => expect(nextDockVisibility({ previousY, y, visible })).toBe(expected));
```

- [ ] **Step 2: Write the failing PHP tests** (`LayoutTest.php`). Define a test route `Route::get('/reports', fn () => Blade::render('<x-ob::sidebar.item href="/reports" icon="inbox" label="Reports" /><x-ob::sidebar.item href="/tasks" icon="check-circle" label="Tasks" />'))`. Then:
  - `GET /reports` sees `aria-current="page"` exactly once, before `aria-label="Tasks"`
  - `dock.item` follows the same active rule
  - `stat selected` has `aria-pressed="true"`
  - `page-header title="Reports"` renders an `<h1>` and contains `x-intersect`
- [ ] **Step 3: Run.** FAIL.
- [ ] **Step 4: Implement.**
- [ ] **Step 5: Run.** PASS.
- [ ] **Step 6: Commit.** `feat(obsidian): app shell, sidebar, dock, page header`

---

### Task 7: Table, property, inline-edit, kanban

**Files:**
- Create components: `table`, `table/head`, `table/row`, `table/cell`, `property`, `inline-edit`, `kanban`, `kanban/column`, `kanban/card`
- Create: `resources/js/lib/inline-edit.js`, `lib/inline-edit.test.js`, `data/inline-edit.js`
- Test: `tests/Feature/DataTest.php`

**Interfaces:**
- `<x-ob::table>`: `rounded-xl border border-hairline bg-surface-1 overflow-hidden`, with an inner `overflow-x-auto` (desktop only). The `head` slot renders `<thead>`, which is sticky with a `glass` background.
- `<x-ob::table.head sortable=false direction=null>`: `text-xs font-medium text-fg-3`. When sortable, it's a button with `aria-sort`; attributes are forwarded (`wire:click`).
- `<x-ob::table.row href=null>`
  - `h-10`, hover `bg-surface-3`, `border-t border-hairline`.
  - With `href`, the first cell wraps an `<a wire:navigate>` stretched over the row (`after:absolute after:inset-0`; the row is `relative`).
  - Attributes are forwarded to the `<tr>`. Pages add `wire:key` and `wire:transition` to rows so Livewire updates cross-fade (spec §3, motion).
  - Below `sm` the row is `display:grid` (stacked).
- `<x-ob::table.cell mobile='meta'>`
  - `mobile` is `primary|meta|hide`. Below `sm`: `primary` is `text-sm text-fg truncate` on the first line, `meta` cells flow inline as `text-xs text-fg-3` on the second, and `hide` is `max-sm:hidden`.
  - Cells use `min-w-0` with `truncate` on text.
- `<x-ob::property label icon=null>`: `grid grid-cols-[8rem_1fr] items-center min-h-9 gap-3`, label in `text-fg-3 text-[13px]`, value in the slot.
- `lib/inline-edit.js`: `export function createInlineEdit({ value, save })` returns `{ value, draft, editing, saving, error, start(), cancel(), async commit() }`.
  - `commit()` does nothing while `saving` is true.
  - Values are compared as `String(x ?? '').trim()`, so select values can be numbers or `null`. When the draft equals the value, it cancels without calling `save`.
  - `save` receives `null` for an empty draft, so `?int` methods like `updateAssignee` get `null`.
  - Otherwise it sets `saving` and awaits `save(draft)`. `save` resolves to an error string or `null`.
  - On `null`: `value = draft`, `editing = false`. On an error string: `error` is set and editing stays open.
- `<x-ob::inline-edit :value action name type='text' :options=[] label placeholder=null>`
  - `obInlineEdit` builds `createInlineEdit` with `save = async (v) => { await $wire.$call(action, v); return $wire.$errors.first(name) ?? null }`. `type="select"` saves immediately on change.
  - Display mode is a button (`aria-label="Edit {label}"`) showing the value, or the placeholder in `text-fg-3`, with hover `bg-surface-3 rounded-md`.
  - Edit mode: Enter calls `commit`, Esc calls `cancel` (registered via the `obLayers` store), and blur calls `commit`.
  - While saving, a spinner shows; then a check icon fades for 1.2s.
  - On error, `text-xs text-danger-fg role="alert"`.
- `<x-ob::kanban>`: `flex gap-4 overflow-x-auto snap-x snap-mandatory lg:snap-none pb-4`; each column is `snap-start`.
- `<x-ob::kanban.column id title count=null sort group>`
  - `w-[85vw] sm:w-80 shrink-0 rounded-xl bg-surface-1/60 border border-hairline`. The header shows the title and a count badge.
  - The list element carries `wire:sort="{sort}" wire:sort:group="{group}" wire:sort:group-id="{id}"`, with `min-h-24`.
  - An `empty` slot renders when there are no cards.
- `<x-ob::kanban.card id>`
  - `wire:sort:item="{id}"`, `rounded-lg bg-surface-2 border border-hairline p-3`, and `[&.sortable-chosen]:shadow-xl [&.sortable-chosen]:scale-[1.02]`. The placeholder is `[&.sortable-ghost]:opacity-30` on a dashed `accent-soft` outline.
  - It includes a `wire:sort:handle` grip icon button (`label="Move"`); the content is wrapped in `wire:sort:ignore`.
  - Before writing these selectors, confirm that `wire:sort` uses SortableJS class names by grepping `vendor/livewire/livewire/dist/livewire.esm.js` for `sortable-chosen`/`ghostClass`, and use whatever class names it actually sets.

- [ ] **Step 1: Write the failing JS tests** (`inline-edit.test.js`)

```js
test('enter then blur saves once', async () => { const save = vi.fn().mockResolvedValue(null); const e = createInlineEdit({ value: 'Old', save }); e.start(); e.draft = 'New'; const p = e.commit(); e.commit(); await p; expect(save).toHaveBeenCalledOnce(); expect(e.value).toBe('New'); expect(e.editing).toBe(false); });
test('unchanged value does not save', async () => { const save = vi.fn(); const e = createInlineEdit({ value: 'Same', save }); e.start(); e.draft = ' Same '; await e.commit(); expect(save).not.toHaveBeenCalled(); expect(e.editing).toBe(false); });
test('an empty draft saves null', async () => { const save = vi.fn().mockResolvedValue(null); const e = createInlineEdit({ value: 3, save }); e.start(); e.draft = ''; await e.commit(); expect(save).toHaveBeenCalledWith(null); });
test('cancel restores the value', () => { const e = createInlineEdit({ value: 'A', save: vi.fn() }); e.start(); e.draft = 'B'; e.cancel(); expect(e.draft).toBe('A'); expect(e.editing).toBe(false); });
test('a server error keeps the field open', async () => { const e = createInlineEdit({ value: 'A', save: vi.fn().mockResolvedValue('Enter a title.') }); e.start(); e.draft = 'x'; await e.commit(); expect(e.editing).toBe(true); expect(e.error).toBe('Enter a title.'); expect(e.value).toBe('A'); });
```

- [ ] **Step 2: Write the failing PHP tests** (`DataTest.php`):
  - `table.row href="/r/1"` contains `href="/r/1"` and `wire:navigate`
  - `table.cell mobile="hide"` contains `max-sm:hidden`
  - `kanban.column id="todo" sort="moveTask" group="tasks"` contains `wire:sort="moveTask"`, `wire:sort:group="tasks"`, and `wire:sort:group-id="todo"`
  - `kanban.card id="7"` contains `wire:sort:item="7"` and `wire:sort:handle`
  - `inline-edit value="Old" action="updateTitle" name="title" label="Title"` contains `aria-label="Edit Title"` and `updateTitle`
- [ ] **Step 3: Run.** FAIL.
- [ ] **Step 4: Implement.**
- [ ] **Step 5: Run.** PASS.
- [ ] **Step 6: Commit.** `feat(obsidian): table, property, inline edit, kanban`

---

### Task 8: Combobox and command palette

**Files:**
- Create components: `combobox`, `command-palette`, `command-palette/group`, `command-palette/item`
- Create: `resources/js/lib/fuzzy.js`, `lib/shortcuts.js` (with tests), `data/combobox.js`, `data/command-palette.js`
- Test: `tests/Feature/CommandTest.php`

**Interfaces:**
- `lib/fuzzy.js`: `export function rank(query: string, items: T[], key = (i) => i.label): T[]`
  - Case-insensitive subsequence match; items with no match are dropped.
  - Scoring per matched character:
    - +10 at a word start (index 0 or after a space, `-`, `/`, or `_`)
    - otherwise +5 when adjacent to the previous match
    - otherwise +1
  - +15 extra when the whole query is a prefix of the text.
  - Sorted by score descending, then text length ascending, then original order. An empty query returns the items unchanged.
- `lib/shortcuts.js`: `export function paletteShortcut(event: KeyboardEvent): boolean`
  - True for `(metaKey || ctrlKey) && key === 'k'`.
  - True for `key === '/'` with no modifiers, unless `event.target` is an `input`, `textarea`, or `select`, or `isContentEditable`.
- `<x-ob::combobox :options multiple=false placeholder label search=null>`
  - `options` is `list<array{value, label, description?, avatar?}>`. Selected values are bound via the forwarded `wire:model` or `x-model`, entangled as `$wire.$entangle(...)` when it's `wire:model`.
  - Without `search`, options are filtered client-side with `rank`. With `search="assigneeQuery"`, the query input carries `wire:model.live.debounce.250ms="{search}"` and the server re-renders `options`.
  - Uses `role="combobox"` with `aria-expanded` and `aria-controls` pointing at a `role="listbox"`; options are `role="option"` with `aria-selected`.
  - Keyboard: ↑/↓ to move, Enter to toggle/select, Backspace on an empty query removes the last chip, Esc closes (via `obLayers`).
  - Multi mode shows chips (`badge` with a remove button `label="Remove {label}"`).
  - Panel: an anchored `glass` popover at `sm` and up, a sheet below `sm`.
- `<x-ob::command-palette :actions=[] placeholder='Search or jump to…'>` with a default slot for server results. Forwarded `wire:model*` goes to the query input.
  - `actions` is `list<array{label, href?, icon?, shortcut?, event?}>`. An `event` entry dispatches that window event instead of navigating.
  - `obCommandPalette` opens on `paletteShortcut` (a `window` keydown listener with `preventDefault`) or on the `command-palette-open` window event.
  - Panel: centered at top 15vh, `max-w-xl glass rounded-xl` at `sm` and up; full screen below `sm`. `scrim`, `x-trap`, registered in `obLayers`.
  - With an empty query it shows "Recent" (from `localStorage['ob-command-recent']`, up to 5, newest first) and then all actions. With a query it shows the actions ranked by `rank`, followed by the slot (server groups).
  - ↑/↓ moves through every visible `[data-ob-command-item]` in DOM order (`aria-selected`), Enter activates the selected item, and activating an `href` item writes it to the recent list.
- `<x-ob::command-palette.group heading>` and `<x-ob::command-palette.item href icon meta=null>` (`data-ob-command-item`, `role="option"`, `wire:navigate`).

- [ ] **Step 1: Write the failing JS tests**

```js
// fuzzy.test.js
const L = (...xs) => xs.map(label => ({ label }));
test('prefix and word starts win', () => expect(rank('nt', L('Accounts', 'New task')).map(i => i.label)).toEqual(['New task', 'Accounts']));
test('ties go to the shorter label', () => expect(rank('rep', L('Report settings', 'Reports', 'Projects')).map(i => i.label)).toEqual(['Reports', 'Report settings']));
test('empty query keeps order', () => expect(rank('', L('b', 'a')).map(i => i.label)).toEqual(['b', 'a']));
// shortcuts.test.js
const ev = (key, opts = {}, target = document.body) => ({ key, metaKey: false, ctrlKey: false, altKey: false, ...opts, target });
test('cmd+k always opens', () => { const input = document.createElement('input'); expect(paletteShortcut(ev('k', { metaKey: true }, input))).toBe(true); });
test('slash opens outside fields', () => expect(paletteShortcut(ev('/'))).toBe(true));
test.each(['input', 'textarea', 'select'])('slash is ignored in %s', tag => expect(paletteShortcut(ev('/', {}, document.createElement(tag)))).toBe(false));
test('slash is ignored in contenteditable', () => expect(paletteShortcut(ev('/', {}, { isContentEditable: true, tagName: 'DIV' }))).toBe(false));
```

- [ ] **Step 2: Write the failing PHP tests** (`CommandTest.php`):
  - `combobox label="Assignee" wire:model="assigneeIds" multiple :options="[...]"` contains `role="combobox"`, `role="listbox"`, `aria-multiselectable="true"`, and `$entangle('assigneeIds')`
  - `command-palette wire:model.live.debounce.150ms="query"` puts `wire:model.live.debounce.150ms="query"` on the `<input`
  - `command-palette.item href="/tasks/1"` contains `data-ob-command-item` and `role="option"`
- [ ] **Step 3: Run.** FAIL.
- [ ] **Step 4: Implement.**
- [ ] **Step 5: Run.** PASS.
- [ ] **Step 6: Commit.** `feat(obsidian): combobox and command palette`

---

### Task 9: Showcase page

**Files:**
- Modify: `packages/obsidian/resources/views/showcase.blade.php`
- Test: extend `tests/Feature/ServiceProviderTest.php`

**Interfaces:**
- Consumes: every component from Tasks 1–8.
- The page is a full HTML document. It renders `<x-ob::theme-script />` and `@vite(['resources/css/app.css', 'resources/js/app.js'])`, then wraps everything in `x-ob::app-shell` with a sidebar, a dock, and `x-ob::toasts`.
- It has one `x-ob::section` per group: tokens (swatches of every colour token), type scale, buttons, forms, overlays, layout, data, command.
- It includes a header toolbar with a `segmented` mode picker and accent swatches that call `$store.obTheme.apply(...)`. Interactive demos use `x-data` and `x-model` only; there's no Livewire component.
- A **"Stress"** section (Review focus 5) contains:
  - a table row, kanban card, page header, and toast, each with a 200-character title
  - the 40-character unbroken token `aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa`
  - a long project name

- [ ] **Step 1: Write the failing test.** Locally, `GET /_obsidian` sees `Stress`, `data-ob-command-item`, `wire:sort:item`, and `role="dialog"`.
- [ ] **Step 2: Run.** FAIL.
- [ ] **Step 3: Build the page.**
- [ ] **Step 4: Run.** PASS. `npm run build` succeeds.
- [ ] **Step 5: Visual check.** Get the URL from Boost `get-absolute-url` for `/_obsidian` and open it in the browser pane. Check:
  - at 1440px and 375px (`resize_window` preset mobile)
  - dark and light, with iris and amber
  - no horizontal scroll at 375px (`document.documentElement.scrollWidth <= innerWidth` via `javascript_tool`)
  - the sheet drags to dismiss, the dock hides on scroll, Esc closes only the top layer, and the palette opens with ⌘K but not with `/` in an input

  Fix anything found before committing.
- [ ] **Step 6: Commit.** `feat(obsidian): showcase`

---

### Task 10: App theming data

**Files:**
- Create: `app/Enums/Appearance.php`, `app/Enums/Accent.php`
- Create: `database/migrations/2026_10_21_000000_replace_theme_with_appearance_and_accent_on_users_table.php` (via `php artisan make:migration`)
- Delete: `app/Enums/Theme.php` (and the empty `app/Enums/Enums/` directory if it's empty)
- Modify: `app/Models/User.php` (`@property`, `#[Fillable]`, casts), `database/factories/UserFactory.php`, `app/Http/Resources/Api/V1/UserResource.php`, `resources/views/partials/head.blade.php`, every layout's `<html>` tag (`grep -rln 'data-theme' resources/views`), `resources/views/pages/settings/⚡appearance.blade.php`
- Test: `tests/Feature/Settings/AppearanceTest.php` (rewrite), `tests/Feature/Database/AppearanceMigrationTest.php` (new), `tests/Feature/Api/V1/MeControllerTest.php`

**Interfaces:**
- `enum Appearance: string { case System = 'system'; case Light = 'light'; case Dark = 'dark'; public function label(): string }`
- `enum Accent: string { case Iris = 'iris'; case Blue = 'blue'; case Teal = 'teal'; case Green = 'green'; case Amber = 'amber'; case Rose = 'rose'; public function label(): string; public function swatch(): string }`. `swatch` returns the spec's base hex.
- Migration `up()`:
  1. Add `appearance` string(16) default `system` and `accent` string(16) default `iris` after `password`.
  2. Update `appearance` to `light` where `theme` is one of `light, cupcake, bumblebee, emerald, corporate, retro, nord, winter`.
  3. Update `appearance` to `dark` where `theme` is one of `dark, black, synthwave, halloween, forest, luxury, night, business`.
  4. Drop `theme`.

  The lists are hard-coded because the enum is deleted. `down()` re-adds `theme` default `light` and sets it to `black` where `appearance = dark`, otherwise `light`; it then drops the two new columns.
- `User` casts `appearance => Appearance::class`, `accent => Accent::class`; fillable replaces `theme` with `appearance` and `accent`.
- `UserResource` returns `id`, `name`, `email`, `appearance` (value), `accent` (value), and `theme` (`'dark'` if `appearance === Appearance::Dark`, else `'light'`). Add a PHPDoc line marking `theme` as kept for the macOS client.
- Layout `<html>` tags:
  - `data-appearance="{{ $user?->appearance->value ?? 'system' }}"`
  - `data-mode="{{ ($user?->appearance ?? Appearance::System) === Appearance::Light ? 'light' : 'dark' }}"` (the server-side default for `system` is dark; `theme-script` corrects it before first paint)
  - `data-accent="{{ $user?->accent->value ?? 'iris' }}"`

  `partials/head` replaces the old inline script with `<x-ob::theme-script :appearance="auth()->user()?->appearance?->value" :accent="auth()->user()?->accent?->value" />`.
- Appearance page:
  - Public `string $appearance`, `string $accent`.
  - `selectAppearance(string $appearance): void` and `selectAccent(string $accent): void` validate via `Appearance::tryFrom` / `Accent::tryFrom`. On an invalid value they add an error on the `appearance` / `accent` key and keep the saved value.
  - On success they save and `dispatch('ob-theme-apply', appearance: …, accent: …)`.
  - The view uses `x-ob::segmented` for the mode (icons `computer-desktop`, `sun`, `moon`) and a radiogroup of accent swatch buttons (`aria-label="{label} accent"`). Clicking applies optimistically via `$store.obTheme.apply` and then calls the Livewire method.

- [ ] **Step 1: Write the failing tests**

```php
// AppearanceMigrationTest.php
it('maps old daisyui themes to appearance', function () {
    $migration = require database_path('migrations/2026_10_21_000000_replace_theme_with_appearance_and_accent_on_users_table.php');
    $migration->down();
    $ids = collect(['night', 'corporate', 'atreyu'])->mapWithKeys(fn (string $theme) => [$theme => DB::table('users')->insertGetId([
        'name' => $theme, 'email' => "{$theme}@atreyu.test", 'password' => 'x', 'theme' => $theme, 'created_at' => now(), 'updated_at' => now(),
    ])])->all();
    $migration->up();
    expect(DB::table('users')->whereKey($ids['night'])->value('appearance'))->toBe('dark')
        ->and(DB::table('users')->whereKey($ids['corporate'])->value('appearance'))->toBe('light')
        ->and(DB::table('users')->whereKey($ids['atreyu'])->value('appearance'))->toBe('system')
        ->and(DB::table('users')->whereKey($ids['night'])->value('accent'))->toBe('iris')
        ->and(Schema::hasColumn('users', 'theme'))->toBeFalse();
});
```

```php
// AppearanceTest.php (replaces the file)
test('appearance and accent are saved and applied', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Livewire::test('pages::settings.appearance')
        ->call('selectAppearance', 'dark')->assertDispatched('ob-theme-apply', appearance: 'dark', accent: 'iris')
        ->call('selectAccent', 'amber')->assertDispatched('ob-theme-apply', appearance: 'dark', accent: 'amber');
    expect($user->refresh())->appearance->toBe(Appearance::Dark)->accent->toBe(Accent::Amber);
});
test('invalid values are rejected', function () {
    $user = User::factory()->create(['appearance' => Appearance::Light]);
    $this->actingAs($user);
    Livewire::test('pages::settings.appearance')->call('selectAppearance', 'synthwave')->assertHasErrors(['appearance'])->call('selectAccent', 'puce')->assertHasErrors(['accent']);
    expect($user->refresh()->appearance)->toBe(Appearance::Light);
});
test('the saved preference is on the html element before paint', function () {
    $user = User::factory()->create(['appearance' => Appearance::Light, 'accent' => Accent::Teal]);
    $this->actingAs($user)->get(route('appearance.edit'))->assertOk()
        ->assertSee('data-appearance="light" data-mode="light" data-accent="teal"', false)
        ->assertDontSee('data-theme=', false);
});
```

Change `MeControllerTest`'s expected JSON to `'appearance' => 'system', 'accent' => 'iris', 'theme' => 'light'`, and add a case for a Dark user that expects `'theme' => 'dark'`.

- [ ] **Step 2: Run.** `php artisan test --compact --filter='Appearance|MeController'` → FAIL.
- [ ] **Step 3: Implement.** Then run `php artisan migrate` locally and `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 4: Run.** The same filter passes. Then run `grep -rn "Theme::\|Enums\\\\Theme\|data-theme\|atreyu-theme" app resources tests database/factories`; expect no hits.
- [ ] **Step 5: Commit.** `feat: appearance and accent preferences replace daisyui themes`

---

### Task 11: App shell, layouts, navigation

**Files:**
- Modify: `resources/views/layouts/app.blade.php`, `layouts/app/sidebar.blade.php`, `layouts/auth.blade.php`, `layouts/auth/{card,simple,split}.blade.php`
- Modify `layouts/app/header.blade.php` only if `grep -rn "app.header" resources/views app` shows it in use; otherwise delete it.
- Modify: `resources/views/components/app-logo.blade.php`, `app-logo-icon.blade.php`, `auth-session-status.blade.php` (restyle with tokens)
- Delete: `resources/views/flux/`, `components/desktop-user-menu.blade.php`, `components/page-header.blade.php`, `components/auth-header.blade.php`, and `components/placeholder-pattern.blade.php` if unused. Replace their usages, found with `grep -rn "x-page-header\|x-auth-header\|x-desktop-user-menu\|x-placeholder-pattern" resources/views`: `x-page-header` becomes `x-ob::page-header`, and `x-auth-header` becomes the auth layout's own heading.
- Test: `tests/Feature/NavigationTest.php`

**Interfaces:**
- Consumes: `app-shell`, `sidebar.*`, `dock.*`, `dropdown`, `menu.*`, `avatar`, `toasts`, and `theme-script` (via the head partial).
- The sidebar has Reports (`inbox-stack`), Projects (`folder`), and Tasks (`check-circle`), in that order. The footer is a `dropdown` whose avatar trigger is labelled with the user's name and whose menu items are Settings (`route('profile.edit')`) and Log out (a `form` posting to `logout`).
- The dock has Reports, Projects, a `dock.action` (icon `plus`, label "New task", `x-on:click="$dispatch('command-palette-open')"`; Task 15 makes the palette's empty state lead with the create actions), Tasks, and the avatar. The avatar is a `dock.item` linking to `route('profile.edit')` with `label="Settings"`.
- The shell renders `<x-ob::toasts />` once. Task 15 mounts the command palette here.
- Auth layouts: centered `card` on `canvas`, `max-w-sm`, logo above.

- [ ] **Step 1: Update `NavigationTest`.** Keep its existing assertions (the `aria-label` order for Reports, Projects, Tasks; no Dashboard; one `>Settings<`). Add:
  - `->assertSee('aria-current="page"', false)`
  - `->assertDontSee('mary-')`
  - `->assertDontSee('drawer')`
  - `expect(substr_count($response->getContent(), 'aria-label="Reports"'))->toBe(2)` (sidebar plus dock)
- [ ] **Step 2: Run.** `php artisan test --compact tests/Feature/NavigationTest.php` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run.** The navigation and auth tests pass: `php artisan test --compact --filter='Navigation|Auth'`.
- [ ] **Step 5: Commit.** `feat: app shell on obsidian`

---

### Task 12: Reports pages

**Files:**
- Modify: `resources/views/livewire/reports/index.blade.php`, `resources/views/livewire/reports/show.blade.php`, `app/Livewire/Reports/Show.php` (`updateTitle`)
- Test: `tests/Feature/Livewire/Reports/ShowTest.php`, `IndexTest.php` (update selectors that reference old markup, if any)

**Interfaces:**
- Consumes: `page-header`, `stat`, `input`, `select`, `table.*`, `badge`, `avatar`, `empty-state`, `property`, `inline-edit`, `modal`, `combobox`, `dropdown`, `menu.*`, `button`, `card`, `toasts`.
- Index:
  - the three triage tiles become `x-ob::stat` (`wire:click="$set('status', …)"`, `:selected`)
  - the filters become `input` (search) plus `select`
  - the list becomes `table` with these cells:

    | Cell | `mobile` |
    |---|---|
    | title | `primary` |
    | project | `meta` |
    | received | `meta` |
    | type | `hide` |
    | reporter | `hide` |

  - priority and status are `badge`s mapped to status colours (urgent→danger, high→warning, normal→info, low→neutral; new→accent, converted→success, wontfix→neutral)
  - an `empty-state` appears when there are no results
- Show: header uses `inline-edit action="updateTitle" name="title"`. The side panel is a stack of `property` rows: status (`inline-edit type="select" action="updateStatus" name="status"`), assignee (`inline-edit type="select" action="updateAssignee"`), project, and reporter. Triage becomes `modal wire:model="showTriage"`. The actions dropdown holds Convert to task, Dismiss / Reopen, and Copy link.
- `Show::updateTitle(string $title): void` throws validation: `Validator::make(['title' => trim($title)], ['title' => ['required', 'string', 'max:255']])->validate()`, then updates. Livewire turns the exception into an error on `title`. It also calls `$this->dispatch('toast', message: 'Title updated', variant: 'success')`.

- [ ] **Step 1: Write the failing tests** (`ShowTest.php`)

```php
it('rejects a blank inline title', function () {
    $report = Report::factory()->create(['title' => 'Original']);
    Livewire::actingAs(User::factory()->create())->test(Show::class, ['report' => $report])
        ->call('updateTitle', '   ')->assertHasErrors(['title' => 'required']);
    expect($report->refresh()->title)->toBe('Original');
});
it('saves an inline title and toasts', function () {
    $report = Report::factory()->create();
    Livewire::actingAs(User::factory()->create())->test(Show::class, ['report' => $report])
        ->call('updateTitle', 'Checkout 500 on submit')->assertHasNoErrors()->assertDispatched('toast');
    expect($report->refresh()->title)->toBe('Checkout 500 on submit');
});
```

- [ ] **Step 2: Run.** `php artisan test --compact tests/Feature/Livewire/Reports` → the new tests FAIL.
- [ ] **Step 3: Implement the views and `updateTitle`.**
- [ ] **Step 4: Run.** `tests/Feature/Livewire/Reports` passes. Run `grep -n "base-\|btn\|x-icon \|x-button\|x-select\|x-input" resources/views/livewire/reports`; expect no hits.
- [ ] **Step 5: Commit.** `feat: reports pages on obsidian`

---

### Task 13: Tasks pages

**Files:**
- Modify: `resources/views/livewire/tasks/index.blade.php`, `resources/views/livewire/tasks/show.blade.php`, `app/Livewire/Tasks/Show.php`
- Test: `tests/Feature/Livewire/Tasks/ShowTest.php`, `IndexTest.php`

**Interfaces:**
- Consumes: `kanban.*` (with the existing `moveTask(int $taskId, int $position, string $status)` handler; the `group="tasks"` and `group-id` contract is unchanged), plus `table.*`, `segmented` (board/list toggle bound to `display`), `modal` (create, `wire:model="showCreate"`), `combobox` (assignee and project in the create modal), `inline-edit`, `property`, `badge`, `avatar`.
- New `Tasks\Show` methods, both using `UpdateTaskDetails` like their siblings:
  - `updateTitle(string $title, UpdateTaskDetails $updateTask): void` validates `['title' => ['required', 'string', 'max:255']]` on the trimmed value.
  - `updatePriority(string $priority, UpdateTaskDetails $updateTask): void` validates `['priority' => ['required', Rule::enum(TaskPriority::class)]]`.
  - Both then call `refreshTask()` and dispatch `toast`.
- Show page: inline-edit for title, status (`updateStatus`), priority (`updatePriority`), assignee (`updateAssignee`), and difficulty (`updateDifficulty`). The existing edit modal stays for description, project, type, and due date, restyled as `x-ob::modal wire:model="showEdit"`.

- [ ] **Step 1: Write the failing tests** (`ShowTest.php`): a blank `updateTitle` gives `assertHasErrors(['title' => 'required'])` and leaves the title unchanged; `updatePriority('urgent')` saves `TaskPriority::Urgent` and dispatches `toast`; `updatePriority('extreme')` gives `assertHasErrors(['priority'])`.
- [ ] **Step 2: Run.** `php artisan test --compact tests/Feature/Livewire/Tasks` → the new tests FAIL. The existing `moveTask` tests must keep passing throughout.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run.** `tests/Feature/Livewire/Tasks` and `tests/Feature/Actions/MoveTaskTest.php` pass. The legacy-markup grep from Task 12 on `resources/views/livewire/tasks` has no hits.
- [ ] **Step 5: Commit.** `feat: tasks board and pages on obsidian`

---

### Task 14: Projects, settings, auth pages

**Files:**
- Modify: `resources/views/livewire/projects/{index,show}.blade.php`, `resources/views/pages/settings/{layout,⚡profile,⚡security}.blade.php`, `resources/views/partials/settings-heading.blade.php`, `resources/views/pages/auth/*.blade.php`, `resources/views/components/passkey-{registration,verify}.blade.php`
- Test: the existing `tests/Feature/Livewire/Projects`, `tests/Feature/Settings`, and `tests/Feature/Auth` suites

**Interfaces:**
- Consumes: forms, `card`, `section`, `table.*`, `empty-state`, `modal`, `button`, `page-header`.
- The settings sub-navigation becomes a `segmented`-style link row on mobile and a left list (`sidebar.item`-style links) at `lg` and up.
- Auth forms: `input`, `password`, a full-width primary `button`, and links in `text-accent-fg`.

- [ ] **Step 1: Run the existing suites as a baseline.** `php artisan test --compact tests/Feature/Livewire/Projects tests/Feature/Settings tests/Feature/Auth` → record the results.
- [ ] **Step 2: Rewrite the views.**
- [ ] **Step 3: Run.** The same suites match the baseline. Run `grep -rnE "<x-(button|input|password|select|textarea|icon|modal|menu|card|nav|main|avatar)\b|base-content|base-[123]00|btn" resources/views`; expect no hits.
- [ ] **Step 4: Commit.** `feat: projects, settings and auth pages on obsidian`

---

### Task 15: Command palette (app)

**Files:**
- Create: `app/Livewire/CommandPalette.php` and its view `resources/views/livewire/command-palette.blade.php` (via `php artisan make:livewire CommandPalette --class --no-interaction`; match the class-based style of `app/Livewire/Reports/Index.php`)
- Modify: `resources/views/layouts/app/sidebar.blade.php` (mount `<livewire:command-palette />` once inside the shell; add a sidebar search button that shows `kbd` ⌘K and dispatches `command-palette-open`)
- Test: `tests/Feature/Livewire/CommandPaletteTest.php`

**Interfaces:**
- `class CommandPalette extends Component { public string $query = ''; public function render(): View }`
  - With `mb_strlen(trim($query)) < 2`, it passes empty collections.
  - Otherwise it passes up to 5 of each: `reports` (title like `%q%`, newest first), `tasks` (title like `%q%` or identifier match, using the same numeric-id parsing as `Tasks\Index::filteredTasksQuery`, `whereNull('archived_at')`), and `projects` (name like `%q%`).
  - Every user can see every record (the app has no policies), so the only scoping is archived exclusion. The route group's `auth` middleware is the boundary.
- View: `<x-ob::command-palette wire:model.live.debounce.150ms="query" :actions="[...]">`.
  - `actions`: New task (`href=route('tasks.index').'?create=1'`, icon `plus`). If `Tasks\Index` has no `create` query support, add `#[Url] public bool $create = false` and open the modal in `mount` when it's true. Then Reports, Projects, Tasks, Settings, and Toggle appearance (`event: 'ob-theme-cycle'`; the `obTheme` store cycles dark → light → system on that event).
  - Results: a `command-palette.group` per non-empty collection, with items linking to the show routes. `meta` shows the task identifier, project name, or report age.

- [ ] **Step 1: Write the failing tests**

```php
it('searches reports, tasks and projects', function () {
    Report::factory()->create(['title' => 'Checkout 500']);
    Task::factory()->create(['title' => 'Fix checkout tax']);
    Project::factory()->create(['name' => 'Checkout']);
    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', 'checkout')
        ->assertSee('Checkout 500')->assertSee('Fix checkout tax')->assertSeeHtml('data-ob-command-item');
});
it('excludes archived tasks', function () {
    Task::factory()->create(['title' => 'Old checkout', 'archived_at' => now()]);
    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)->set('query', 'checkout')->assertDontSee('Old checkout');
});
it('does not query for one character', function () {
    Task::factory()->create(['title' => 'C task']);
    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)->set('query', 'c')->assertDontSee('C task');
});
it('caps each group at five', function () {
    Task::factory()->count(7)->sequence(fn ($s) => ['title' => "Checkout {$s->index}"])->create();
    $html = Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)->set('query', 'checkout')->html();
    expect(substr_count($html, '/tasks/'))->toBe(5);
});
```

- [ ] **Step 2: Run.** FAIL.
- [ ] **Step 3: Implement.** Run pint.
- [ ] **Step 4: Run.** PASS. `NavigationTest` still passes.
- [ ] **Step 5: Commit.** `feat: command palette`

---

### Task 16: Remove MaryUI and DaisyUI, final verification

**Files:**
- Modify: `composer.json` (remove `robsontenorio/mary`), `package.json` (remove `daisyui`), `resources/css/app.css` (remove the `@plugin 'daisyui'` block and the Mary `@source` line)
- Delete: `config/mary.php` if present

**Steps:**
- [ ] **Step 1: Remove the dependencies.** Run `composer remove robsontenorio/mary` and `npm uninstall daisyui`.
- [ ] **Step 2: Check that nothing still references them.** `grep -rniE "mary|daisy|data-theme|x-mary" app config resources routes tests bootstrap composer.json package.json` → no hits outside `CHANGELOG`-type files.
- [ ] **Step 3: Build and test.**
  - `npm run build` succeeds.
  - `npx vp test --run packages/obsidian` passes.
  - `cd packages/obsidian && vendor/bin/pest` passes.
  - Run `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 4: Visual pass with the browser pane.** Cover `/reports`, `/reports/{id}`, `/tasks` (board and list), `/tasks/{id}`, `/projects`, `/settings/appearance`, and `/login`, at 1440px and 375px, in dark and light, with iris and amber. On each page:
  - no horizontal scroll at 375px
  - dock visible below 1024px
  - dropdowns become sheets below 640px
  - ⌘K palette works
  - inline-edit saves and shows its error
  - kanban drag persists after reload
  - toasts sit above the dock

  Fix regressions, re-run the affected tests, and record what was checked in the commit body.
- [ ] **Step 5: Commit.** `chore: remove maryui and daisyui`
- [ ] **Step 6: Ask the user to run the full suite.** `php artisan test --compact` (per `CLAUDE.md`).
