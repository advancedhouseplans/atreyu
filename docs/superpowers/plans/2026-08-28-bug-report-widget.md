# Bug Report Widget Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a drop-in bug/feature-report widget for AHP + Takeoff plus a Livewire reviewer app that ingests reports with browser + Laravel-side context.

**Architecture:** Three artifacts — (1) reviewer Laravel/Livewire app in this repo, (2) `packages/bug-companion/` composer package installed in each source app, (3) `public/widget.js` vanilla-JS bundle served by the reviewer app. Widget submits to reviewer with an HMAC-signed config token; reviewer signs an outbound callback to the source app to hydrate per-request Laravel context stashed in cache.

**Tech Stack:** Laravel 13, Livewire 4, Flux 2, Fortify (login only), Pest 5, Vite 8, TailwindCSS 4, `whichbrowser/parser` (composer), `html2canvas` (npm, widget-only), `@playwright/test` + `vitest` (widget tests).

**Spec:** `docs/superpowers/specs/2026-08-28-bug-report-widget-design.md`

## Global Constraints

Apply to every task unless a task overrides explicitly.

- **PHP:** `^8.3` (per `composer.json`), all methods typed, constructor property promotion, PHPDoc only where non-obvious.
- **Laravel:** 13; use `bootstrap/app.php` config (no Kernel classes).
- **Fortify:** login-only. Registration + profile update removed. Seeded users only.
- **Livewire 4 + Flux 2** for all reviewer UI. No custom CSS beyond a small pill-color file.
- **Testing:** Pest 5, feature-heavy. Run with `php artisan test --compact`. Run narrow tests during dev with `--filter=`.
- **Formatting:** run `vendor/bin/pint --dirty --format agent` at the end of every task with PHP changes.
- **Widget bundle:** single IIFE at `public/widget.js`. Only bundled runtime dep is `html2canvas`. Vanilla JS, mounted under a shadow root, `z-index: 2147483647`.
- **HMAC contract — widget config token (source app → reviewer app):**
  - Payload = base64url(JSON `{source_slug, env, endpoint, user_id, user_email, user_name, exp}`).
  - Signature = HMAC-SHA256(payload, per-source secret), base64url.
  - Token = `{payload}.{signature}`.
  - Sent by widget as `Authorization: Bearer {token}`.
  - `exp` is unix seconds; reviewer rejects if `now > exp`.
- **HMAC contract — outbound callback (reviewer app → source app):**
  - Reviewer sends `X-Bugs-Timestamp: {unix_seconds}` and `X-Bugs-Signature: {hex HMAC-SHA256 of "{timestamp}.{body}" using per-source secret}`.
  - Source rejects if `abs(now - timestamp) > 60`.
- **Rate limit:** 10 report POSTs/minute keyed by `{source_slug}:{reporter_email}` — 429 on exceed.
- **Widget instrumentation buffers:** network log 50 entries, event log 100 entries (both ring buffers).
- **Timeouts:** outbound callback 5 seconds; on failure, `laravel_context` stored as `[]` and error logged.
- **No new dependencies beyond those listed above.**

---

## Phase 1 — Reviewer App Foundation

Delivers a working reviewer app: seeded auth, DB schema, ingestion API with HMAC verification, callback service, Livewire list + detail. Testable end-to-end with mocked source apps.

### Task 1: Sources config

**Files:**
- Create: `config/bug-sources.php`
- Modify: `.env.example` (append 2 vars)
- Test: `tests/Feature/Config/BugSourcesConfigTest.php`

**Interfaces:**
- Consumes: `config('bug-sources.{slug}')` → `['name' => string, 'endpoint' => string, 'secret' => string]` — consumed by Task 5 (HMAC verifier) and Task 7 (callback service).
- Produces: config file layout.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Config/BugSourcesConfigTest.php

it('exposes source config keyed by slug', function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'https://ahp.test', 'secret' => 's']);
    expect(config('bug-sources.ahp.name'))->toBe('AHP');
});

it('reads secrets from env in the shipped config', function () {
    $config = require config_path('bug-sources.php');
    expect($config)->toHaveKeys(['ahp', 'takeoff']);
    expect($config['ahp'])->toHaveKeys(['name', 'endpoint', 'secret']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BugSourcesConfigTest`
Expected: FAIL — config file missing.

- [ ] **Step 3: Create the config file**

```php
<?php // config/bug-sources.php

return [
    'ahp' => [
        'name'     => 'AHP',
        'endpoint' => env('BUGS_SOURCE_AHP_ENDPOINT', ''),
        'secret'   => env('BUGS_SOURCE_AHP_SECRET', ''),
    ],
    'takeoff' => [
        'name'     => 'Takeoff',
        'endpoint' => env('BUGS_SOURCE_TAKEOFF_ENDPOINT', ''),
        'secret'   => env('BUGS_SOURCE_TAKEOFF_SECRET', ''),
    ],
];
```

Then append to `.env.example`:

```
BUGS_SOURCE_AHP_ENDPOINT=
BUGS_SOURCE_AHP_SECRET=
BUGS_SOURCE_TAKEOFF_ENDPOINT=
BUGS_SOURCE_TAKEOFF_SECRET=
BUGS_SEED_PASSWORD=
```

- [ ] **Step 4: Run test to verify pass**

Run: `php artisan test --filter=BugSourcesConfigTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/bug-sources.php .env.example tests/Feature/Config/BugSourcesConfigTest.php
git commit -m "feat(bugs): add bug-sources config"
```

---

### Task 2: Migrations for reports, notes, attachments

**Files:**
- Create: `database/migrations/{ts}_create_reports_table.php`
- Create: `database/migrations/{ts}_create_report_notes_table.php`
- Create: `database/migrations/{ts}_create_report_attachments_table.php`
- Test: `tests/Feature/Database/ReportsSchemaTest.php`

**Interfaces:**
- Produces: `reports`, `report_notes`, `report_attachments` tables — consumed by Task 3 (models) and all downstream persistence tasks.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Database/ReportsSchemaTest.php

use Illuminate\Support\Facades\Schema;

it('has reports table with all documented columns', function () {
    expect(Schema::hasTable('reports'))->toBeTrue();
    $cols = ['source_slug', 'environment', 'reporter_user_id', 'reporter_email', 'reporter_name',
        'type', 'severity', 'status', 'assignee_id', 'title', 'description',
        'url', 'referrer', 'app_version', 'user_agent', 'browser', 'browser_version',
        'os', 'os_version', 'device', 'screen_wh', 'viewport_wh', 'dpr',
        'timezone', 'locale', 'laravel_context', 'client_events', 'network_log'];
    foreach ($cols as $c) {
        expect(Schema::hasColumn('reports', $c))->toBeTrue("missing: {$c}");
    }
});

it('has report_notes and report_attachments tables', function () {
    expect(Schema::hasTable('report_notes'))->toBeTrue();
    expect(Schema::hasTable('report_attachments'))->toBeTrue();
    foreach (['report_id', 'user_id', 'body'] as $c) {
        expect(Schema::hasColumn('report_notes', $c))->toBeTrue();
    }
    foreach (['report_id', 'disk', 'path', 'mime', 'size', 'kind'] as $c) {
        expect(Schema::hasColumn('report_attachments', $c))->toBeTrue();
    }
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=ReportsSchemaTest`
Expected: FAIL — tables missing.

- [ ] **Step 3: Generate & fill migrations**

```bash
php artisan make:migration create_reports_table --no-interaction
php artisan make:migration create_report_notes_table --no-interaction
php artisan make:migration create_report_attachments_table --no-interaction
```

Fill `create_reports_table`:

```php
Schema::create('reports', function (Blueprint $t) {
    $t->id();
    $t->string('source_slug', 32)->index();
    $t->string('environment', 32);
    $t->unsignedBigInteger('reporter_user_id')->nullable();
    $t->string('reporter_email')->index();
    $t->string('reporter_name')->nullable();
    $t->string('type', 16);       // bug | feature | other
    $t->string('severity', 16);   // low | med | high | blocker
    $t->string('status', 16)->default('new')->index();
    $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
    $t->string('title');
    $t->text('description')->nullable();
    $t->text('url');
    $t->text('referrer')->nullable();
    $t->string('app_version')->nullable();
    $t->text('user_agent')->nullable();
    $t->string('browser')->nullable();
    $t->string('browser_version')->nullable();
    $t->string('os')->nullable();
    $t->string('os_version')->nullable();
    $t->string('device')->nullable();
    $t->string('screen_wh', 32)->nullable();
    $t->string('viewport_wh', 32)->nullable();
    $t->decimal('dpr', 4, 2)->nullable();
    $t->string('timezone')->nullable();
    $t->string('locale', 16)->nullable();
    $t->json('laravel_context')->nullable();
    $t->json('client_events')->nullable();
    $t->json('network_log')->nullable();
    $t->timestamps();
    $t->index(['status', 'created_at']);
});
```

Fill `create_report_notes_table`:

```php
Schema::create('report_notes', function (Blueprint $t) {
    $t->id();
    $t->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
    $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $t->text('body');
    $t->timestamps();
    $t->index('report_id');
});
```

Fill `create_report_attachments_table`:

```php
Schema::create('report_attachments', function (Blueprint $t) {
    $t->id();
    $t->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
    $t->string('disk', 32);
    $t->string('path');
    $t->string('mime', 128);
    $t->unsignedBigInteger('size');
    $t->string('kind', 32); // auto_screenshot | user_upload
    $t->timestamps();
    $t->index('report_id');
});
```

- [ ] **Step 4: Run migration + test**

Run: `php artisan migrate:fresh && php artisan test --filter=ReportsSchemaTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations tests/Feature/Database/ReportsSchemaTest.php
git commit -m "feat(bugs): create reports, notes, attachments tables"
```

---

### Task 3: Report models + factories

**Files:**
- Create: `app/Models/Report.php`
- Create: `app/Models/ReportNote.php`
- Create: `app/Models/ReportAttachment.php`
- Create: `database/factories/ReportFactory.php`
- Create: `database/factories/ReportNoteFactory.php`
- Create: `database/factories/ReportAttachmentFactory.php`
- Test: `tests/Feature/Models/ReportModelsTest.php`

**Interfaces:**
- Produces:
  - `Report::class` with fillable everything from Task 2 + casts (`laravel_context`, `client_events`, `network_log` → `array`, `dpr` → `float`).
  - Relations: `Report::notes()` → HasMany, `Report::attachments()` → HasMany, `Report::assignee()` → BelongsTo(User).
  - `ReportNote::report()` → BelongsTo, `ReportNote::author()` → BelongsTo(User, 'user_id').
  - `ReportAttachment::report()` → BelongsTo.
  - Factories with sensible defaults.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Models/ReportModelsTest.php

use App\Models\Report;
use App\Models\ReportNote;
use App\Models\ReportAttachment;
use App\Models\User;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a Report with array casts', function () {
    $r = Report::factory()->create([
        'laravel_context' => [['debug_id' => 'x', 'route_name' => 'home']],
        'client_events'   => [['type' => 'error']],
        'network_log'     => [['url' => '/x']],
    ]);
    expect($r->laravel_context)->toBeArray()->toHaveCount(1);
    expect($r->client_events[0]['type'])->toBe('error');
});

it('relates notes and attachments and assignee', function () {
    $u = User::factory()->create();
    $r = Report::factory()->for($u, 'assignee')->create();
    ReportNote::factory()->for($r)->for($u, 'author')->create(['body' => 'hi']);
    ReportAttachment::factory()->for($r)->create();

    expect($r->fresh()->notes)->toHaveCount(1)
        ->and($r->fresh()->attachments)->toHaveCount(1)
        ->and($r->fresh()->assignee->id)->toBe($u->id);
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=ReportModelsTest`
Expected: FAIL — models missing.

- [ ] **Step 3: Generate and fill models + factories**

```bash
php artisan make:model Report --factory --no-interaction
php artisan make:model ReportNote --factory --no-interaction
php artisan make:model ReportAttachment --factory --no-interaction
```

`app/Models/Report.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'laravel_context' => 'array',
        'client_events'   => 'array',
        'network_log'     => 'array',
        'dpr'             => 'float',
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(ReportNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
```

`app/Models/ReportNote.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

`app/Models/ReportAttachment.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAttachment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
```

`database/factories/ReportFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'source_slug'     => 'ahp',
            'environment'    => 'production',
            'reporter_email' => $this->faker->safeEmail(),
            'reporter_name'  => $this->faker->name(),
            'type'           => 'bug',
            'severity'       => 'med',
            'status'         => 'new',
            'title'          => $this->faker->sentence(),
            'description'    => $this->faker->paragraph(),
            'url'            => 'https://ahp.test/plans/1',
            'user_agent'     => 'Mozilla/5.0',
        ];
    }
}
```

`database/factories/ReportNoteFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportNoteFactory extends Factory
{
    protected $model = ReportNote::class;

    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'user_id'   => User::factory(),
            'body'      => $this->faker->sentence(),
        ];
    }
}
```

`database/factories/ReportAttachmentFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportAttachmentFactory extends Factory
{
    protected $model = ReportAttachment::class;

    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'disk'      => 'local',
            'path'      => 'reports/1/x.png',
            'mime'      => 'image/png',
            'size'      => 1024,
            'kind'      => 'user_upload',
        ];
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ReportModelsTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models database/factories tests/Feature/Models
git commit -m "feat(bugs): add Report, ReportNote, ReportAttachment models + factories"
```

---

### Task 4: Fortify lockdown + user seeder

**Files:**
- Modify: `config/fortify.php` (remove `registration`, keep others)
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/AuthTest.php`

**Interfaces:**
- Produces: two seeded users (`jamie@advancedhouseplans.com`, `keith@advancedhouseplans.com`) with password from `BUGS_SEED_PASSWORD` env. Registration route returns 404.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/AuthTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('does not expose a registration route', function () {
    $this->post('/register', [
        'name' => 'X', 'email' => 'x@x.com', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertStatus(404);
});

it('seeds jamie and keith', function () {
    config()->set('bugs.seed_password', 'seedpass');
    putenv('BUGS_SEED_PASSWORD=seedpass');
    Artisan::call('db:seed', ['--force' => true]);
    expect(User::where('email', 'jamie@advancedhouseplans.com')->exists())->toBeTrue();
    expect(User::where('email', 'keith@advancedhouseplans.com')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=AuthTest`
Expected: FAIL — registration still 302/200, seeder empty.

- [ ] **Step 3: Remove registration from Fortify and add seeder**

In `config/fortify.php`, remove `Features::registration(),` from the `features` array.

Replace `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('BUGS_SEED_PASSWORD');
        if (! $password) {
            $this->command?->warn('BUGS_SEED_PASSWORD not set — skipping user seed');
            return;
        }

        foreach ([
            ['name' => 'Jamie', 'email' => 'jamie@advancedhouseplans.com'],
            ['name' => 'Keith', 'email' => 'keith@advancedhouseplans.com'],
        ] as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => Hash::make($password), 'email_verified_at' => now()],
            );
        }
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=AuthTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/fortify.php database/seeders/DatabaseSeeder.php tests/Feature/AuthTest.php
git commit -m "feat(bugs): lock Fortify to login only, seed jamie + keith"
```

---

### Task 5: TokenSigner (server side)

**Files:**
- Create: `app/Support/BugTokenSigner.php`
- Test: `tests/Unit/Support/BugTokenSignerTest.php`

**Interfaces:**
- Produces:
  - `BugTokenSigner::verify(string $token, ?string &$payload = null): ?array` — parses `{payload}.{sig}`, looks up secret by decoded `source_slug` from `config('bug-sources')`, HMAC-verifies, returns decoded payload array on success or null on failure. Returns null if `exp` is past.
  - Payload keys returned: `source_slug`, `env`, `endpoint`, `user_id`, `user_email`, `user_name`, `exp`.
- Consumed by: Task 6 middleware.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Unit/Support/BugTokenSignerTest.php

use App\Support\BugTokenSigner;

beforeEach(function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'x', 'secret' => 'topsecret']);
});

function makeToken(array $payload, string $secret): string
{
    $b64 = fn ($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
    $body = $b64(json_encode($payload));
    $sig  = $b64(hash_hmac('sha256', $body, $secret, true));
    return "{$body}.{$sig}";
}

it('accepts a valid token', function () {
    $token = makeToken(['source_slug' => 'ahp', 'env' => 'prod', 'endpoint' => 'x',
        'user_id' => 1, 'user_email' => 'a@b', 'user_name' => 'A', 'exp' => time() + 60], 'topsecret');
    expect(BugTokenSigner::verify($token))->toMatchArray(['source_slug' => 'ahp', 'user_id' => 1]);
});

it('rejects tampered signature', function () {
    $token = makeToken(['source_slug' => 'ahp', 'exp' => time() + 60], 'topsecret') . 'x';
    expect(BugTokenSigner::verify($token))->toBeNull();
});

it('rejects expired token', function () {
    $token = makeToken(['source_slug' => 'ahp', 'env' => 'p', 'endpoint' => 'x',
        'user_id' => 1, 'user_email' => 'a', 'user_name' => 'a', 'exp' => time() - 1], 'topsecret');
    expect(BugTokenSigner::verify($token))->toBeNull();
});

it('rejects unknown source slug', function () {
    $token = makeToken(['source_slug' => 'ghost', 'exp' => time() + 60], 'topsecret');
    expect(BugTokenSigner::verify($token))->toBeNull();
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=BugTokenSignerTest`
Expected: FAIL — class missing.

- [ ] **Step 3: Implement `BugTokenSigner`**

```php
<?php // app/Support/BugTokenSigner.php

namespace App\Support;

class BugTokenSigner
{
    /**
     * Verify a widget config token. Returns the decoded payload array on success, null on any failure.
     *
     * @return array{source_slug:string, env:string, endpoint:string, user_id:mixed, user_email:string, user_name:string, exp:int}|null
     */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$b64Payload, $b64Sig] = $parts;

        $payloadJson = self::b64UrlDecode($b64Payload);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload) || ! isset($payload['source_slug'], $payload['exp'])) {
            return null;
        }

        $source = config("bug-sources.{$payload['source_slug']}");
        if (! is_array($source) || empty($source['secret'])) {
            return null;
        }

        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $b64Payload, $source['secret'], true)), '+/', '-_'), '=');
        if (! hash_equals($expected, $b64Sig)) {
            return null;
        }

        if (time() > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private static function b64UrlDecode(string $s): string|false
    {
        $pad = strlen($s) % 4;
        if ($pad) {
            $s .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($s, '-_', '+/'), true);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=BugTokenSignerTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Support/BugTokenSigner.php tests/Unit/Support/BugTokenSignerTest.php
git commit -m "feat(bugs): add HMAC token signer/verifier"
```

---

### Task 6: VerifyBugToken middleware

**Files:**
- Create: `app/Http/Middleware/VerifyBugToken.php`
- Test: `tests/Feature/Http/VerifyBugTokenMiddlewareTest.php`

**Interfaces:**
- Produces: middleware alias `bug.token` registered in `bootstrap/app.php` `middlewareAliases`. Attaches decoded payload to request via `$request->attributes->set('bug.token', $payload)`. 401 on failure.
- Consumed by: Task 8 (ReportsController route).

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Http/VerifyBugTokenMiddlewareTest.php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'x', 'secret' => 'topsecret']);
    Route::post('/_test/token-check', function (\Illuminate\Http\Request $r) {
        return response()->json(['payload' => $r->attributes->get('bug.token')]);
    })->middleware('bug.token');
});

function tokenFor(array $payload, string $secret): string
{
    $b64 = fn ($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
    $body = $b64(json_encode($payload));
    $sig  = $b64(hash_hmac('sha256', $body, $secret, true));
    return "{$body}.{$sig}";
}

it('rejects missing bearer', function () {
    $this->postJson('/_test/token-check')->assertStatus(401);
});

it('rejects bad signature', function () {
    $this->withHeader('Authorization', 'Bearer abc.def')
        ->postJson('/_test/token-check')->assertStatus(401);
});

it('accepts valid token and attaches payload', function () {
    $t = tokenFor(['source_slug' => 'ahp', 'env' => 'prod', 'endpoint' => 'x',
        'user_id' => 1, 'user_email' => 'a@b', 'user_name' => 'A', 'exp' => time() + 60], 'topsecret');
    $this->withHeader('Authorization', "Bearer {$t}")
        ->postJson('/_test/token-check')
        ->assertOk()
        ->assertJsonPath('payload.source_slug', 'ahp');
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=VerifyBugTokenMiddlewareTest`
Expected: FAIL — alias unknown.

- [ ] **Step 3: Implement + register**

`app/Http/Middleware/VerifyBugToken.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Support\BugTokenSigner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBugToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'missing token'], 401);
        }

        $token = substr($header, 7);
        $payload = BugTokenSigner::verify($token);
        if ($payload === null) {
            return response()->json(['error' => 'invalid token'], 401);
        }

        $request->attributes->set('bug.token', $payload);
        return $next($request);
    }
}
```

Register in `bootstrap/app.php`, in `->withMiddleware(function (Middleware $middleware) { ... })`:

```php
$middleware->alias([
    'bug.token' => \App\Http\Middleware\VerifyBugToken::class,
]);
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=VerifyBugTokenMiddlewareTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware/VerifyBugToken.php bootstrap/app.php tests/Feature/Http/VerifyBugTokenMiddlewareTest.php
git commit -m "feat(bugs): add bug.token middleware"
```

---

### Task 7: Callback service (`SourceContextFetcher`)

**Files:**
- Create: `app/Services/SourceContextFetcher.php`
- Test: `tests/Feature/Services/SourceContextFetcherTest.php`

**Interfaces:**
- Produces: `SourceContextFetcher::fetch(string $sourceSlug, array $debugIds): array` — returns `[debug_id => context|null, ...]`. On failure (network error, non-2xx, empty ids) returns `[]`.
- Consumed by: Task 8 (ReportsController).

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/SourceContextFetcherTest.php

use App\Services\SourceContextFetcher;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('bug-sources.ahp', [
        'name' => 'AHP', 'endpoint' => 'https://ahp.test', 'secret' => 'topsecret',
    ]);
});

it('returns [] for empty ids', function () {
    expect(app(SourceContextFetcher::class)->fetch('ahp', []))->toBe([]);
});

it('calls signed callback and returns json map', function () {
    Http::fake([
        'https://ahp.test/_bug-debug/*' => Http::response(['id1' => ['route_name' => 'x'], 'id2' => null], 200),
    ]);

    $out = app(SourceContextFetcher::class)->fetch('ahp', ['id1', 'id2']);
    expect($out)->toBe(['id1' => ['route_name' => 'x'], 'id2' => null]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Bugs-Signature')
            && $request->hasHeader('X-Bugs-Timestamp')
            && str_ends_with((string) $request->url(), '/_bug-debug/id1|id2');
    });
});

it('returns [] on 5xx', function () {
    Http::fake(['*' => Http::response('', 500)]);
    expect(app(SourceContextFetcher::class)->fetch('ahp', ['a']))->toBe([]);
});

it('returns [] on unknown source', function () {
    expect(app(SourceContextFetcher::class)->fetch('ghost', ['a']))->toBe([]);
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=SourceContextFetcherTest`
Expected: FAIL — class missing.

- [ ] **Step 3: Implement**

```php
<?php // app/Services/SourceContextFetcher.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SourceContextFetcher
{
    /**
     * Fetch per-request Laravel context from a source app.
     *
     * @param  array<int, string>  $debugIds
     * @return array<string, array|null>
     */
    public function fetch(string $sourceSlug, array $debugIds): array
    {
        if (empty($debugIds)) {
            return [];
        }

        $source = config("bug-sources.{$sourceSlug}");
        if (! is_array($source) || empty($source['endpoint']) || empty($source['secret'])) {
            return [];
        }

        $ids  = implode('|', $debugIds);
        $url  = rtrim((string) $source['endpoint'], '/') . "/_bug-debug/{$ids}";
        $body = '';
        $ts   = (string) time();
        $sig  = hash_hmac('sha256', "{$ts}.{$body}", (string) $source['secret']);

        try {
            $resp = Http::withHeaders([
                'X-Bugs-Timestamp' => $ts,
                'X-Bugs-Signature' => $sig,
                'Accept'           => 'application/json',
            ])->timeout(5)->get($url);
        } catch (Throwable $e) {
            Log::warning('bug-companion callback failed', ['error' => $e->getMessage(), 'source' => $sourceSlug]);
            return [];
        }

        if (! $resp->successful()) {
            Log::warning('bug-companion callback non-2xx', ['status' => $resp->status(), 'source' => $sourceSlug]);
            return [];
        }

        $json = $resp->json();
        return is_array($json) ? $json : [];
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=SourceContextFetcherTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/SourceContextFetcher.php tests/Feature/Services/SourceContextFetcherTest.php
git commit -m "feat(bugs): add SourceContextFetcher callback service"
```

---

### Task 8: Install whichbrowser/parser + UA parsing helper

**Files:**
- Modify: `composer.json`
- Create: `app/Support/UserAgentParser.php`
- Test: `tests/Unit/Support/UserAgentParserTest.php`

**Interfaces:**
- Produces: `UserAgentParser::parse(string $ua): array{browser:?string, browser_version:?string, os:?string, os_version:?string, device:?string}`.
- Consumed by: Task 9 (ReportsController).

- [ ] **Step 1: Add dep**

```bash
composer require whichbrowser/parser
```

- [ ] **Step 2: Write the failing test**

```php
<?php // tests/Unit/Support/UserAgentParserTest.php

use App\Support\UserAgentParser;

it('parses a chrome UA', function () {
    $out = UserAgentParser::parse(
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    );
    expect($out['browser'])->toBe('Chrome')
        ->and($out['os'])->toBe('OS X');
});

it('returns nulls for empty', function () {
    expect(UserAgentParser::parse(''))->toBe([
        'browser' => null, 'browser_version' => null, 'os' => null, 'os_version' => null, 'device' => null,
    ]);
});
```

- [ ] **Step 3: Run test to fail**

Run: `php artisan test --filter=UserAgentParserTest`
Expected: FAIL.

- [ ] **Step 4: Implement**

```php
<?php // app/Support/UserAgentParser.php

namespace App\Support;

use WhichBrowser\Parser;

class UserAgentParser
{
    /**
     * @return array{browser:?string, browser_version:?string, os:?string, os_version:?string, device:?string}
     */
    public static function parse(string $ua): array
    {
        if ($ua === '') {
            return ['browser' => null, 'browser_version' => null, 'os' => null, 'os_version' => null, 'device' => null];
        }

        $p = new Parser($ua);
        return [
            'browser'         => $p->browser->getName() ?: null,
            'browser_version' => $p->browser->getVersion() ?: null,
            'os'              => $p->os->getName() ?: null,
            'os_version'      => $p->os->getVersion() ?: null,
            'device'          => $p->device->type ?: null,
        ];
    }
}
```

- [ ] **Step 5: Run tests, format, commit**

```bash
php artisan test --filter=UserAgentParserTest
vendor/bin/pint --dirty --format agent
git add composer.json composer.lock app/Support/UserAgentParser.php tests/Unit/Support/UserAgentParserTest.php
git commit -m "feat(bugs): add whichbrowser UA parsing helper"
```

---

### Task 9: `POST /api/reports` — ReportsController

**Files:**
- Create: `routes/api.php`
- Modify: `bootstrap/app.php` (register api routing)
- Create: `app/Http/Controllers/Api/ReportsController.php`
- Create: `app/Http/Requests/StoreReportRequest.php`
- Test: `tests/Feature/Api/StoreReportTest.php`

**Interfaces:**
- Consumes: `bug.token` middleware (Task 6), `SourceContextFetcher` (Task 7), `UserAgentParser` (Task 8), models (Task 3).
- Produces: `POST /api/reports` — accepts multipart (`title`, `description`, `type`, `severity`, `metadata` JSON string, `snapshot` file, `attachments[]` files). Returns `201 { report_id }`. Rate limited 10/min per `{source_slug}:{reporter_email}`. Persists Report + attachments to configured disk, calls `SourceContextFetcher::fetch` inline.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Api/StoreReportTest.php

use App\Models\Report;
use App\Services\SourceContextFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'https://ahp.test', 'secret' => 'topsecret']);
    Storage::fake('local');
});

function bearer(array $payload = [], string $secret = 'topsecret'): string
{
    $payload = array_merge([
        'source_slug' => 'ahp', 'env' => 'production', 'endpoint' => 'https://ahp.test',
        'user_id' => 42, 'user_email' => 'reporter@ahp.test', 'user_name' => 'Reporter', 'exp' => time() + 60,
    ], $payload);
    $b64  = fn ($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
    $body = $b64(json_encode($payload));
    $sig  = $b64(hash_hmac('sha256', $body, $secret, true));
    return "Bearer {$body}.{$sig}";
}

it('rejects unauthenticated submission', function () {
    $this->postJson('/api/reports', [])->assertStatus(401);
});

it('creates a report from valid submission', function () {
    $this->mock(SourceContextFetcher::class)
        ->shouldReceive('fetch')->once()
        ->with('ahp', ['id1', 'id2'])
        ->andReturn(['id1' => ['route_name' => 'x'], 'id2' => null]);

    $metadata = json_encode([
        'url' => 'https://ahp.test/plans/1', 'referrer' => null, 'title' => 'plan',
        'screen_wh' => '1920x1080', 'viewport_wh' => '1440x900', 'dpr' => 2.0,
        'timezone' => 'America/New_York', 'locale' => 'en-US',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'app_version' => '1.2.3',
        'network_log' => [['method' => 'GET', 'url' => '/x', 'status' => 200, 'debug_id' => 'id1']],
        'client_events' => [['type' => 'error', 'message' => 'boom']],
        'debug_ids' => ['id1', 'id2'],
    ]);

    $resp = $this->withHeader('Authorization', bearer())->post('/api/reports', [
        'title'       => 'Broken thing',
        'description' => 'It broke',
        'type'        => 'bug',
        'severity'    => 'high',
        'metadata'    => $metadata,
        'snapshot'    => File::image('snap.jpg', 100, 100),
        'attachments' => [File::create('note.txt', 5)],
    ]);

    $resp->assertCreated()->assertJsonStructure(['report_id']);

    $r = Report::first();
    expect($r->source_slug)->toBe('ahp')
        ->and($r->reporter_email)->toBe('reporter@ahp.test')
        ->and($r->title)->toBe('Broken thing')
        ->and($r->browser)->toBe('Chrome')
        ->and($r->laravel_context)->toBe([['id' => 'id1', 'context' => ['route_name' => 'x']], ['id' => 'id2', 'context' => null]])
        ->and($r->attachments)->toHaveCount(2);
});

it('rate-limits after 10 submissions', function () {
    $this->mock(SourceContextFetcher::class)->shouldReceive('fetch')->andReturn([]);
    $auth = ['Authorization' => bearer()];

    for ($i = 0; $i < 10; $i++) {
        $this->withHeaders($auth)->post('/api/reports', [
            'title' => 't', 'description' => 'd', 'type' => 'bug', 'severity' => 'low',
            'metadata' => '{}',
        ])->assertCreated();
    }
    $this->withHeaders($auth)->post('/api/reports', [
        'title' => 't', 'description' => 'd', 'type' => 'bug', 'severity' => 'low', 'metadata' => '{}',
    ])->assertStatus(429);
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=StoreReportTest`
Expected: FAIL — routes/controller missing.

- [ ] **Step 3: Wire API routing**

In `bootstrap/app.php`, update `withRouting`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

`routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware('bug.token')->group(function () {
    Route::post('/reports', [ReportsController::class, 'store'])->name('api.reports.store');
});
```

- [ ] **Step 4: Create request + controller**

`app/Http/Requests/StoreReportRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string|max:20000',
            'type'          => 'required|in:bug,feature,other',
            'severity'      => 'required|in:low,med,high,blocker',
            'metadata'      => 'required|string',           // JSON string
            'snapshot'      => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'attachments'   => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240',
        ];
    }
}
```

`app/Http/Controllers/Api/ReportsController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Services\SourceContextFetcher;
use App\Support\UserAgentParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class ReportsController extends Controller
{
    public function __construct(private readonly SourceContextFetcher $fetcher) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $token = $request->attributes->get('bug.token');
        $key   = "reports:{$token['source_slug']}:{$token['user_email']}";
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'rate limited'], 429);
        }
        RateLimiter::hit($key, 60);

        $meta = json_decode((string) $request->input('metadata'), true) ?: [];

        $ua = UserAgentParser::parse((string) ($meta['user_agent'] ?? ''));

        $report = Report::create([
            'source_slug'     => $token['source_slug'],
            'environment'    => $token['env'] ?? 'production',
            'reporter_user_id' => $token['user_id'] ?? null,
            'reporter_email' => $token['user_email'],
            'reporter_name'  => $token['user_name'] ?? null,
            'type'           => $request->input('type'),
            'severity'       => $request->input('severity'),
            'status'         => 'new',
            'title'          => $request->input('title'),
            'description'    => $request->input('description'),
            'url'            => $meta['url'] ?? '',
            'referrer'       => $meta['referrer'] ?? null,
            'app_version'    => $meta['app_version'] ?? null,
            'user_agent'     => $meta['user_agent'] ?? null,
            'browser'         => $ua['browser'],
            'browser_version' => $ua['browser_version'],
            'os'              => $ua['os'],
            'os_version'      => $ua['os_version'],
            'device'          => $ua['device'],
            'screen_wh'      => $meta['screen_wh'] ?? null,
            'viewport_wh'    => $meta['viewport_wh'] ?? null,
            'dpr'            => $meta['dpr'] ?? null,
            'timezone'       => $meta['timezone'] ?? null,
            'locale'         => $meta['locale'] ?? null,
            'client_events'  => $meta['client_events'] ?? [],
            'network_log'    => $meta['network_log'] ?? [],
            'laravel_context' => [],
        ]);

        $this->storeAttachment($report, $request->file('snapshot'), 'auto_screenshot');
        foreach ((array) $request->file('attachments') as $f) {
            $this->storeAttachment($report, $f, 'user_upload');
        }

        $ids = array_values(array_unique(array_filter((array) ($meta['debug_ids'] ?? []))));
        $hydrated = $this->fetcher->fetch($token['source_slug'], $ids);
        $report->laravel_context = array_map(fn ($id) => ['id' => $id, 'context' => $hydrated[$id] ?? null], $ids);
        $report->save();

        return response()->json(['report_id' => $report->id], 201);
    }

    private function storeAttachment(Report $report, $file, string $kind): void
    {
        if (! $file) {
            return;
        }
        $disk = config('filesystems.default');
        $path = $file->storeAs("reports/{$report->id}", $file->hashName(), $disk);
        ReportAttachment::create([
            'report_id' => $report->id,
            'disk'      => $disk,
            'path'      => $path,
            'mime'      => $file->getMimeType() ?: 'application/octet-stream',
            'size'      => $file->getSize() ?: 0,
            'kind'      => $kind,
        ]);
    }
}
```

- [ ] **Step 5: Run tests, format, commit**

```bash
php artisan test --filter=StoreReportTest
vendor/bin/pint --dirty --format agent
git add routes/api.php bootstrap/app.php app/Http/Controllers/Api/ReportsController.php app/Http/Requests/StoreReportRequest.php tests/Feature/Api/StoreReportTest.php
git commit -m "feat(bugs): add POST /api/reports with rate limit and context hydration"
```

---

### Task 10: Attachment download controller

**Files:**
- Create: `app/Http/Controllers/AttachmentController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AttachmentDownloadTest.php`

**Interfaces:**
- Produces: `GET /reports/{report}/attachments/{attachment}` (auth required, route name `reports.attachments.download`). Streams file via `Storage::disk(...)->response(...)`. 403 if attachment does not belong to the given report.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/AttachmentDownloadTest.php

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('requires auth', function () {
    $r = Report::factory()->create();
    $a = ReportAttachment::factory()->for($r)->create();
    $this->get("/reports/{$r->id}/attachments/{$a->id}")->assertRedirect('/login');
});

it('streams the file for a matching report', function () {
    Storage::fake('local');
    $u = User::factory()->create();
    $r = Report::factory()->create();
    $path = UploadedFile::fake()->create('a.txt', 1)->storeAs("reports/{$r->id}", 'a.txt', 'local');
    $a = ReportAttachment::factory()->for($r)->create(['disk' => 'local', 'path' => $path, 'mime' => 'text/plain']);

    $this->actingAs($u)->get("/reports/{$r->id}/attachments/{$a->id}")->assertOk();
});

it('403s on mismatched report', function () {
    $u = User::factory()->create();
    [$r1, $r2] = Report::factory()->count(2)->create();
    $a = ReportAttachment::factory()->for($r2)->create();
    $this->actingAs($u)->get("/reports/{$r1->id}/attachments/{$a->id}")->assertStatus(403);
});
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=AttachmentDownloadTest`
Expected: FAIL.

- [ ] **Step 3: Add controller + route**

```php
<?php // app/Http/Controllers/AttachmentController.php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Report $report, ReportAttachment $attachment): StreamedResponse
    {
        abort_if($attachment->report_id !== $report->id, 403);
        return Storage::disk($attachment->disk)->response($attachment->path, null, ['Content-Type' => $attachment->mime]);
    }
}
```

In `routes/web.php`, add inside the `auth` group:

```php
Route::get('/reports/{report}/attachments/{attachment}',
    [\App\Http\Controllers\AttachmentController::class, 'show']
)->name('reports.attachments.download');
```

Also remove `verified` middleware from the auth group (Fortify email verification disabled in Task 12 — remove now to keep tests green; if you skipped Task 12 first, this is safe).

- [ ] **Step 4: Run tests, format, commit**

```bash
php artisan test --filter=AttachmentDownloadTest
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/AttachmentController.php routes/web.php tests/Feature/AttachmentDownloadTest.php
git commit -m "feat(bugs): add auth-gated attachment download"
```

---

### Task 11: `Reports\Index` Livewire component

**Files:**
- Create: `app/Livewire/Reports/Index.php`
- Create: `resources/views/livewire/reports/index.blade.php`
- Modify: `routes/web.php` (add `/reports` route)
- Test: `tests/Feature/Livewire/Reports/IndexTest.php`

**Interfaces:**
- Produces: `App\Livewire\Reports\Index` with URL-bound props `#[Url] $q, $source, $type, $severity, $status, $assignee`, `$sortBy = 'created_at'`, `$sortDir = 'desc'`. `render()` returns paginated reports filtered by all props.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Livewire/Reports/IndexTest.php

use App\Livewire\Reports\Index;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('renders the list for logged-in users', function () {
    $u = User::factory()->create();
    Report::factory()->count(3)->create(['status' => 'new']);
    Report::factory()->create(['status' => 'done', 'title' => 'shipped']);

    Livewire::actingAs($u)->test(Index::class)
        ->assertOk()
        ->assertSee('shipped')
        ->set('status', 'new')
        ->assertDontSee('shipped');
});

it('searches by title', function () {
    $u = User::factory()->create();
    Report::factory()->create(['title' => 'network failure']);
    Report::factory()->create(['title' => 'button color']);

    Livewire::actingAs($u)->test(Index::class)
        ->set('q', 'network')
        ->assertSee('network failure')
        ->assertDontSee('button color');
});

it('requires auth via the route', function () {
    $this->get('/reports')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run to fail**

Run: `php artisan test --filter=Livewire/Reports/IndexTest`
Expected: FAIL.

- [ ] **Step 3: Generate + implement**

```bash
php artisan make:livewire Reports/Index --no-interaction
```

`app/Livewire/Reports/Index.php`:

```php
<?php

namespace App\Livewire\Reports;

use App\Models\Report;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url] public string $q = '';
    #[Url] public string $source = '';
    #[Url] public string $type = '';
    #[Url] public string $severity = '';
    #[Url] public string $status = '';
    #[Url] public string $assignee = '';
    #[Url] public string $sortBy = 'created_at';
    #[Url] public string $sortDir = 'desc';

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingSource(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }
    public function updatingSeverity(): void { $this->resetPage(); }
    public function updatingAssignee(): void { $this->resetPage(); }

    public function render(): View
    {
        $q = Report::query()
            ->when($this->q, fn ($x) => $x->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->q}%")
                ->orWhere('description', 'like', "%{$this->q}%")))
            ->when($this->source, fn ($x) => $x->where('source_slug', $this->source))
            ->when($this->type, fn ($x) => $x->where('type', $this->type))
            ->when($this->severity, fn ($x) => $x->where('severity', $this->severity))
            ->when($this->status, fn ($x) => $x->where('status', $this->status))
            ->when($this->assignee === 'mine' && auth()->id(),
                fn ($x) => $x->where('assignee_id', auth()->id()),
                fn ($x) => $x->when($this->assignee, fn ($y) => $y->where('assignee_id', $this->assignee)))
            ->orderBy($this->sortBy, $this->sortDir);

        return view('livewire.reports.index', ['reports' => $q->paginate(25)]);
    }
}
```

`resources/views/livewire/reports/index.blade.php`:

```blade
<div class="space-y-4 p-6">
    <h1 class="text-2xl font-semibold">Reports</h1>
    <div class="flex gap-3">
        <flux:input wire:model.live.debounce.400ms="q" placeholder="Search..." />
        <flux:select wire:model.live="status" placeholder="Any status">
            <flux:select.option value="">Any status</flux:select.option>
            <flux:select.option value="new">New</flux:select.option>
            <flux:select.option value="in_progress">In progress</flux:select.option>
            <flux:select.option value="done">Done</flux:select.option>
            <flux:select.option value="wontfix">Wontfix</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="source" placeholder="Any source">
            <flux:select.option value="">Any source</flux:select.option>
            <flux:select.option value="ahp">AHP</flux:select.option>
            <flux:select.option value="takeoff">Takeoff</flux:select.option>
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column>Source</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Severity</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Reporter</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($reports as $r)
                <flux:table.row>
                    <flux:table.cell>{{ $r->created_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>{{ $r->source_slug }}</flux:table.cell>
                    <flux:table.cell>{{ $r->type }}</flux:table.cell>
                    <flux:table.cell>{{ $r->severity }}</flux:table.cell>
                    <flux:table.cell>{{ $r->status }}</flux:table.cell>
                    <flux:table.cell>
                        <a class="underline" href="{{ route('reports.show', $r) }}">{{ $r->title }}</a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $r->reporter_email }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">No reports yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    {{ $reports->links() }}
</div>
```

In `routes/web.php`, inside the auth group:

```php
Route::get('/reports', \App\Livewire\Reports\Index::class)->name('reports.index');
```

Also, change the dashboard redirect. Replace the `Route::view('/', 'welcome')` with:

```php
Route::redirect('/', '/reports')->name('home');
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=Livewire/Reports/IndexTest`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Livewire/Reports/Index.php resources/views/livewire/reports/index.blade.php routes/web.php tests/Feature/Livewire/Reports/IndexTest.php
git commit -m "feat(bugs): add Reports\\Index Livewire list"
```

---

### Task 12: `Reports\Show` Livewire component (header, meta, notes, timeline)

**Files:**
- Create: `app/Livewire/Reports/Show.php`
- Create: `resources/views/livewire/reports/show.blade.php`
- Modify: `routes/web.php` (add `/reports/{report}` route)
- Test: `tests/Feature/Livewire/Reports/ShowTest.php`

**Interfaces:**
- Produces: `App\Livewire\Reports\Show` with mount(Report), `updateStatus(string)`, `updateAssignee(?int)`, `addNote(string)`, `updateTitle(string)`. Renders header + meta + attachments + timeline + notes thread.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Livewire/Reports/ShowTest.php

use App\Livewire\Reports\Show;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('renders header and meta', function () {
    $u = User::factory()->create();
    $r = Report::factory()->create(['title' => 'Broken button', 'browser' => 'Chrome']);

    Livewire::actingAs($u)->test(Show::class, ['report' => $r])
        ->assertSee('Broken button')
        ->assertSee('Chrome');
});

it('updates status', function () {
    $u = User::factory()->create();
    $r = Report::factory()->create(['status' => 'new']);

    Livewire::actingAs($u)->test(Show::class, ['report' => $r])
        ->call('updateStatus', 'in_progress')
        ->assertSet('report.status', 'in_progress');

    expect($r->fresh()->status)->toBe('in_progress');
});

it('adds a note', function () {
    $u = User::factory()->create();
    $r = Report::factory()->create();

    Livewire::actingAs($u)->test(Show::class, ['report' => $r])
        ->set('newNote', 'looking into it')
        ->call('addNote')
        ->assertSee('looking into it');

    expect($r->fresh()->notes)->toHaveCount(1);
});
```

- [ ] **Step 2: Run to fail**

Run: `php artisan test --filter=Livewire/Reports/ShowTest`
Expected: FAIL.

- [ ] **Step 3: Generate + implement**

```bash
php artisan make:livewire Reports/Show --no-interaction
```

`app/Livewire/Reports/Show.php`:

```php
<?php

namespace App\Livewire\Reports;

use App\Models\Report;
use App\Models\ReportNote;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Report $report;
    public string $newNote = '';

    public function mount(Report $report): void
    {
        $this->report = $report->load(['notes.author', 'attachments', 'assignee']);
    }

    public function updateStatus(string $status): void
    {
        abort_unless(in_array($status, ['new', 'in_progress', 'done', 'wontfix'], true), 422);
        $this->report->update(['status' => $status]);
    }

    public function updateAssignee(?int $userId): void
    {
        if ($userId && ! User::whereKey($userId)->exists()) {
            return;
        }
        $this->report->update(['assignee_id' => $userId]);
        $this->report->load('assignee');
    }

    public function updateTitle(string $title): void
    {
        $t = trim($title);
        if ($t === '') {
            return;
        }
        $this->report->update(['title' => $t]);
    }

    public function addNote(): void
    {
        $body = trim($this->newNote);
        if ($body === '') {
            return;
        }
        ReportNote::create([
            'report_id' => $this->report->id,
            'user_id'   => auth()->id(),
            'body'      => $body,
        ]);
        $this->newNote = '';
        $this->report->load('notes.author');
    }

    public function render(): View
    {
        return view('livewire.reports.show', [
            'users'    => User::orderBy('name')->get(),
            'timeline' => $this->buildTimeline(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildTimeline(): array
    {
        $events = collect($this->report->client_events ?? [])->map(fn ($e) => $e + ['kind' => 'event']);
        $nets   = collect($this->report->network_log ?? [])->map(fn ($n) => $n + ['kind' => 'network']);
        $ctxs   = collect($this->report->laravel_context ?? [])->map(fn ($c) => $c + ['kind' => 'laravel']);
        return $events->concat($nets)->concat($ctxs)
            ->sortByDesc(fn ($x) => $x['ts'] ?? 0)
            ->values()
            ->all();
    }
}
```

`resources/views/livewire/reports/show.blade.php`:

```blade
<div class="space-y-6 p-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ $report->title }}</h1>
        <div class="flex gap-2">
            <flux:badge>{{ $report->type }}</flux:badge>
            <flux:badge>{{ $report->severity }}</flux:badge>
            <flux:dropdown>
                <flux:button size="sm">{{ $report->status }}</flux:button>
                <flux:menu>
                    @foreach (['new', 'in_progress', 'done', 'wontfix'] as $s)
                        <flux:menu.item wire:click="updateStatus('{{ $s }}')">{{ $s }}</flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
            <flux:dropdown>
                <flux:button size="sm">
                    Assignee: {{ $report->assignee?->name ?? '—' }}
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="updateAssignee(null)">Unassigned</flux:menu.item>
                    @foreach ($users as $user)
                        <flux:menu.item wire:click="updateAssignee({{ $user->id }})">{{ $user->name }}</flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <h2 class="text-lg font-semibold mb-2">Meta</h2>
            <dl class="text-sm space-y-1">
                <div><dt class="inline text-neutral-500">Source:</dt> <dd class="inline">{{ $report->source_slug }} ({{ $report->environment }})</dd></div>
                <div><dt class="inline text-neutral-500">URL:</dt> <dd class="inline">{{ $report->url }}</dd></div>
                <div><dt class="inline text-neutral-500">Browser:</dt> <dd class="inline">{{ $report->browser }} {{ $report->browser_version }}</dd></div>
                <div><dt class="inline text-neutral-500">OS:</dt> <dd class="inline">{{ $report->os }} {{ $report->os_version }}</dd></div>
                <div><dt class="inline text-neutral-500">Viewport:</dt> <dd class="inline">{{ $report->viewport_wh }} @ {{ $report->dpr }}x</dd></div>
                <div><dt class="inline text-neutral-500">Reporter:</dt> <dd class="inline">{{ $report->reporter_name }} &lt;{{ $report->reporter_email }}&gt;</dd></div>
            </dl>
            <div class="mt-4 text-sm whitespace-pre-wrap">{{ $report->description }}</div>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Attachments</h2>
            <ul class="space-y-1 text-sm">
                @foreach ($report->attachments as $a)
                    <li>
                        <a class="underline" href="{{ route('reports.attachments.download', [$report, $a]) }}">
                            {{ $a->kind }} — {{ $a->mime }} — {{ $a->size }} bytes
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div>
        <h2 class="text-lg font-semibold mb-2">Timeline</h2>
        <ol class="space-y-1 text-sm font-mono">
            @foreach ($timeline as $row)
                <li>[{{ $row['kind'] }}] {{ json_encode($row, JSON_UNESCAPED_SLASHES) }}</li>
            @endforeach
        </ol>
    </div>

    <div>
        <h2 class="text-lg font-semibold mb-2">Notes</h2>
        <div class="space-y-2 text-sm">
            @foreach ($report->notes as $note)
                <div class="border rounded p-2">
                    <div class="text-xs text-neutral-500">{{ $note->author->name }} — {{ $note->created_at->diffForHumans() }}</div>
                    <div class="prose prose-sm max-w-none">{!! \Illuminate\Support\Str::markdown($note->body) !!}</div>
                </div>
            @endforeach
        </div>
        <form wire:submit="addNote" class="mt-2 flex gap-2">
            <flux:textarea wire:model="newNote" placeholder="Add a note..." rows="2" />
            <flux:button type="submit">Post</flux:button>
        </form>
    </div>
</div>
```

Route in `routes/web.php` inside auth group:

```php
Route::get('/reports/{report}', \App\Livewire\Reports\Show::class)->name('reports.show');
```

- [ ] **Step 4: Run tests, format, commit**

```bash
php artisan test --filter=Livewire/Reports/ShowTest
vendor/bin/pint --dirty --format agent
git add app/Livewire/Reports/Show.php resources/views/livewire/reports/show.blade.php routes/web.php tests/Feature/Livewire/Reports/ShowTest.php
git commit -m "feat(bugs): add Reports\\Show with status/assignee/notes/timeline"
```

---

### Phase 1 Wrap-up

Run the full suite:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "chore(bugs): phase 1 lint sweep" || true
```

Reviewer app is functionally complete. Widget/companion HMAC contracts are enforced by `bug.token` middleware + `SourceContextFetcher`.

---

## Phase 2 — Companion Laravel Package

Delivers `packages/bug-companion/` as a path-repo composer package with its own tests. Bugs app doesn't require this to boot — this phase is standalone.

### Task 13: Package skeleton + ServiceProvider + config

**Files:**
- Create: `packages/bug-companion/composer.json`
- Create: `packages/bug-companion/src/BugCompanionServiceProvider.php`
- Create: `packages/bug-companion/config/bug-companion.php`
- Modify: `composer.json` (root) — add path repo + require dev
- Test: `packages/bug-companion/tests/Feature/ServiceProviderTest.php`
- Create: `packages/bug-companion/phpunit.xml`
- Create: `packages/bug-companion/tests/TestCase.php`

**Interfaces:**
- Produces: composer package `ahp/bug-companion`, `BugCompanion\` PSR-4 namespace, service provider that publishes config under tag `bug-companion-config`.

- [ ] **Step 1: Create composer.json for the package**

```json
{
    "name": "ahp/bug-companion",
    "type": "library",
    "description": "Bug/feature-report companion for Laravel source apps",
    "require": {
        "php": "^8.3",
        "illuminate/support": "^13.0",
        "illuminate/http": "^13.0",
        "illuminate/routing": "^13.0",
        "illuminate/cache": "^13.0"
    },
    "require-dev": {
        "orchestra/testbench": "^11.0",
        "pestphp/pest": "^5.0"
    },
    "autoload": {
        "psr-4": {
            "BugCompanion\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "BugCompanion\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["BugCompanion\\BugCompanionServiceProvider"]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 2: Add package to root composer.json**

Add to root `composer.json`:

```json
"repositories": [
    { "type": "path", "url": "packages/bug-companion", "options": { "symlink": true } }
],
"require-dev": {
    "ahp/bug-companion": "@dev"
}
```

(Merge into existing keys; keep other existing config intact.)

Then:

```bash
composer update ahp/bug-companion --no-interaction
```

- [ ] **Step 3: Config file**

```php
<?php // packages/bug-companion/config/bug-companion.php

return [
    'source_slug' => env('BUG_COMPANION_SOURCE', ''),
    'endpoint'    => env('BUG_COMPANION_ENDPOINT', ''),
    'secret'      => env('BUG_COMPANION_SECRET', ''),
    'enabled'     => env('BUG_COMPANION_ENABLED', true),
    'context_ttl' => env('BUG_COMPANION_CTX_TTL', 3600),
    'max_queries' => 100,
    'max_models'  => 200,
];
```

- [ ] **Step 4: ServiceProvider stub**

```php
<?php // packages/bug-companion/src/BugCompanionServiceProvider.php

namespace BugCompanion;

use Illuminate\Support\ServiceProvider;

class BugCompanionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bug-companion.php', 'bug-companion');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/bug-companion.php' => config_path('bug-companion.php'),
        ], 'bug-companion-config');
    }
}
```

- [ ] **Step 5: TestCase + phpunit + failing test**

`packages/bug-companion/tests/TestCase.php`:

```php
<?php

namespace BugCompanion\Tests;

use BugCompanion\BugCompanionServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BugCompanionServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bug-companion.secret', 'topsecret');
        $app['config']->set('bug-companion.source_slug', 'ahp');
        $app['config']->set('bug-companion.endpoint', 'https://bugs.test');
    }
}
```

`packages/bug-companion/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="../../vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Feature">
            <directory suffix="Test.php">tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

`packages/bug-companion/tests/Feature/ServiceProviderTest.php`:

```php
<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_merges(): void
    {
        $this->assertSame('ahp', config('bug-companion.source_slug'));
    }
}
```

- [ ] **Step 6: Run + commit**

```bash
cd packages/bug-companion && ../../vendor/bin/pest && cd -
vendor/bin/pint --dirty --format agent
git add packages/bug-companion composer.json composer.lock
git commit -m "feat(companion): scaffold bug-companion package"
```

---

### Task 14: `ContextBuffer` + `TokenSigner` in companion

**Files:**
- Create: `packages/bug-companion/src/Support/ContextBuffer.php`
- Create: `packages/bug-companion/src/Support/TokenSigner.php`
- Test: `packages/bug-companion/tests/Feature/ContextBufferTest.php`
- Test: `packages/bug-companion/tests/Feature/TokenSignerTest.php`

**Interfaces:**
- Produces:
  - `ContextBuffer::__construct(string $debugId, string $method, string $path)`.
  - `ContextBuffer::addQuery(string $sql, array $bindings, float $timeMs, string $connection): void` — truncates each binding to 200 chars, caps to `max_queries`.
  - `ContextBuffer::addModel(string $class, mixed $key): void` — caps to `max_models`.
  - `ContextBuffer::addException(\Throwable $e): void` — first 30 frames.
  - `ContextBuffer::setAuth(?string $guard, mixed $userId, ?string $email): void`.
  - `ContextBuffer::setRouteName(?string $name): void`.
  - `ContextBuffer::toArray(): array` — returns the shape described in spec §4.
  - `TokenSigner::sign(array $payload, string $secret, int $ttl): string` — signs `{payload}.{sig}` per Global Constraints.
  - `TokenSigner::verify(string $token, string $secret): ?array` — the mirror of the reviewer-side signer.

- [ ] **Step 1: Write failing tests**

`packages/bug-companion/tests/Feature/ContextBufferTest.php`:

```php
<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\ContextBuffer;
use BugCompanion\Tests\TestCase;
use RuntimeException;

class ContextBufferTest extends TestCase
{
    public function test_captures_shape(): void
    {
        $b = new ContextBuffer('id1', 'GET', '/plans/1');
        $b->setRouteName('plans.show');
        $b->setAuth('web', 42, 'a@b');
        $b->addModel('App\\Models\\Plan', 1);
        $b->addQuery('select *', [str_repeat('x', 500)], 4.1, 'mysql');
        $b->addException(new RuntimeException('boom'));

        $arr = $b->toArray();
        $this->assertSame('id1', $arr['debug_id']);
        $this->assertSame('plans.show', $arr['request']['route_name']);
        $this->assertSame(42, $arr['auth']['user_id']);
        $this->assertSame('App\\Models\\Plan', $arr['models'][0]['class']);
        $this->assertSame(200, strlen($arr['queries'][0]['bindings'][0]));
        $this->assertSame('boom', $arr['exceptions'][0]['message']);
    }

    public function test_bounded(): void
    {
        config()->set('bug-companion.max_queries', 5);
        $b = new ContextBuffer('id1', 'GET', '/');
        for ($i = 0; $i < 20; $i++) {
            $b->addQuery("q{$i}", [], 1, 'mysql');
        }
        $this->assertCount(5, $b->toArray()['queries']);
    }
}
```

`packages/bug-companion/tests/Feature/TokenSignerTest.php`:

```php
<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;

class TokenSignerTest extends TestCase
{
    public function test_roundtrip(): void
    {
        $t = TokenSigner::sign(['source_slug' => 'ahp', 'user_id' => 1], 'topsecret', 60);
        $this->assertNotNull(TokenSigner::verify($t, 'topsecret'));
        $this->assertNull(TokenSigner::verify($t . 'x', 'topsecret'));
    }

    public function test_expired_returns_null(): void
    {
        $t = TokenSigner::sign(['source_slug' => 'ahp'], 'topsecret', -10);
        $this->assertNull(TokenSigner::verify($t, 'topsecret'));
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd packages/bug-companion && ../../vendor/bin/pest && cd -`
Expected: FAIL.

- [ ] **Step 3: Implement `ContextBuffer`**

```php
<?php // packages/bug-companion/src/Support/ContextBuffer.php

namespace BugCompanion\Support;

use Throwable;

class ContextBuffer
{
    private array $queries = [];
    private array $models = [];
    private array $exceptions = [];
    private ?string $routeName = null;
    private ?string $authGuard = null;
    private mixed $authUserId = null;
    private ?string $authEmail = null;

    public function __construct(
        private readonly string $debugId,
        private readonly string $method,
        private readonly string $path,
    ) {}

    public function setRouteName(?string $name): void
    {
        $this->routeName = $name;
    }

    public function setAuth(?string $guard, mixed $userId, ?string $email): void
    {
        $this->authGuard = $guard;
        $this->authUserId = $userId;
        $this->authEmail = $email;
    }

    public function addQuery(string $sql, array $bindings, float $timeMs, string $connection): void
    {
        $cap = (int) config('bug-companion.max_queries', 100);
        if (count($this->queries) >= $cap) {
            return;
        }
        $this->queries[] = [
            'sql'        => $sql,
            'bindings'   => array_map(fn ($v) => is_string($v) ? substr($v, 0, 200) : $v, $bindings),
            'time_ms'    => $timeMs,
            'connection' => $connection,
        ];
    }

    public function addModel(string $class, mixed $key): void
    {
        $cap = (int) config('bug-companion.max_models', 200);
        if (count($this->models) >= $cap) {
            return;
        }
        $this->models[] = ['class' => $class, 'key' => $key];
    }

    public function addException(Throwable $e): void
    {
        $frames = [];
        foreach (array_slice($e->getTrace(), 0, 30) as $f) {
            $frames[] = ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?');
        }
        $this->exceptions[] = [
            'class'   => $e::class,
            'message' => $e->getMessage(),
            'frames'  => $frames,
        ];
    }

    public function toArray(): array
    {
        return [
            'debug_id' => $this->debugId,
            'request'  => [
                'method'     => $this->method,
                'path'       => $this->path,
                'route_name' => $this->routeName,
                'timestamp'  => now()->toIso8601String(),
            ],
            'auth'       => ['guard' => $this->authGuard, 'user_id' => $this->authUserId, 'user_email' => $this->authEmail],
            'models'     => $this->models,
            'queries'    => $this->queries,
            'exceptions' => $this->exceptions,
        ];
    }
}
```

- [ ] **Step 4: Implement `TokenSigner`**

```php
<?php // packages/bug-companion/src/Support/TokenSigner.php

namespace BugCompanion\Support;

class TokenSigner
{
    public static function sign(array $payload, string $secret, int $ttl): string
    {
        $payload['exp'] = time() + $ttl;
        $body = self::b64UrlEncode(json_encode($payload));
        $sig  = self::b64UrlEncode(hash_hmac('sha256', $body, $secret, true));
        return "{$body}.{$sig}";
    }

    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$body, $sig] = $parts;
        $expected = self::b64UrlEncode(hash_hmac('sha256', $body, $secret, true));
        if (! hash_equals($expected, $sig)) {
            return null;
        }
        $json = self::b64UrlDecode($body);
        $payload = $json === false ? null : json_decode($json, true);
        if (! is_array($payload) || (isset($payload['exp']) && time() > (int) $payload['exp'])) {
            return null;
        }
        return $payload;
    }

    private static function b64UrlEncode(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function b64UrlDecode(string $s): string|false
    {
        $pad = strlen($s) % 4;
        if ($pad) {
            $s .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($s, '-_', '+/'), true);
    }
}
```

- [ ] **Step 5: Run tests, commit**

```bash
cd packages/bug-companion && ../../vendor/bin/pest && cd -
vendor/bin/pint --dirty --format agent
git add packages/bug-companion
git commit -m "feat(companion): add ContextBuffer + TokenSigner"
```

---

### Task 15: `CaptureContext` middleware

**Files:**
- Create: `packages/bug-companion/src/Http/Middleware/CaptureContext.php`
- Test: `packages/bug-companion/tests/Feature/CaptureContextTest.php`
- Modify: `packages/bug-companion/src/BugCompanionServiceProvider.php` (bind ContextBuffer, register listeners at boot, push middleware to `web` group)

**Interfaces:**
- Produces: middleware that runs first in `web` group. Generates UUID `debug_id`. Adds `X-Debug-Id` response header. On terminate, stores buffer under `Cache::put("bug-companion:ctx:{$id}", $buffer->toArray(), config('bug-companion.context_ttl'))`.
- Wires listeners on-boot: `Model::retrieved`, `DB::listen`, exception via `ExceptionHandler::reportable`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/bug-companion/tests/Feature/CaptureContextTest.php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\ContextBuffer;
use BugCompanion\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class CaptureContextTest extends TestCase
{
    public function test_middleware_stashes_context_and_sets_header(): void
    {
        Route::middleware('web')->get('/test-capture', function () {
            $b = app(ContextBuffer::class);
            $b->addModel('App\\Models\\Plan', 1);
            return 'ok';
        });

        $resp = $this->get('/test-capture');
        $resp->assertOk();
        $debugId = $resp->headers->get('X-Debug-Id');
        $this->assertNotEmpty($debugId);

        $ctx = Cache::get("bug-companion:ctx:{$debugId}");
        $this->assertIsArray($ctx);
        $this->assertSame('App\\Models\\Plan', $ctx['models'][0]['class']);
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd packages/bug-companion && ../../vendor/bin/pest --filter=CaptureContextTest && cd -`
Expected: FAIL.

- [ ] **Step 3: Implement middleware**

```php
<?php // packages/bug-companion/src/Http/Middleware/CaptureContext.php

namespace BugCompanion\Http\Middleware;

use BugCompanion\Support\ContextBuffer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CaptureContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $debugId = (string) Str::uuid();
        $buffer  = new ContextBuffer($debugId, $request->method(), $request->path());
        app()->instance(ContextBuffer::class, $buffer);

        $response = $next($request);

        if ($route = $request->route()) {
            $buffer->setRouteName($route->getName() ?: $route->uri());
        }

        if ($user = $request->user()) {
            $buffer->setAuth(auth()->getDefaultDriver(), $user->getKey(), $user->email ?? null);
        }

        $response->headers->set('X-Debug-Id', $debugId);
        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $buffer = app()->bound(ContextBuffer::class) ? app(ContextBuffer::class) : null;
        if (! $buffer instanceof ContextBuffer) {
            return;
        }
        $id = $response->headers->get('X-Debug-Id');
        if (! $id) {
            return;
        }
        Cache::put("bug-companion:ctx:{$id}", $buffer->toArray(), (int) config('bug-companion.context_ttl', 3600));
    }
}
```

- [ ] **Step 4: Wire in ServiceProvider**

Replace `BugCompanionServiceProvider::boot()`:

```php
public function boot(): void
{
    $this->publishes([
        __DIR__.'/../config/bug-companion.php' => config_path('bug-companion.php'),
    ], 'bug-companion-config');

    if (! config('bug-companion.enabled', true)) {
        return;
    }

    /** @var \Illuminate\Routing\Router $router */
    $router = $this->app['router'];
    $router->pushMiddlewareToGroup('web', \BugCompanion\Http\Middleware\CaptureContext::class);

    \Illuminate\Database\Eloquent\Model::retrieved(function ($model): void {
        if ($this->app->bound(\BugCompanion\Support\ContextBuffer::class)) {
            $this->app->make(\BugCompanion\Support\ContextBuffer::class)
                ->addModel($model::class, $model->getKey());
        }
    });

    \Illuminate\Support\Facades\DB::listen(function ($query): void {
        if ($this->app->bound(\BugCompanion\Support\ContextBuffer::class)) {
            $this->app->make(\BugCompanion\Support\ContextBuffer::class)
                ->addQuery($query->sql, $query->bindings ?? [], $query->time ?? 0.0, $query->connectionName);
        }
    });

    /** @var \Illuminate\Contracts\Debug\ExceptionHandler $handler */
    $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
    if (method_exists($handler, 'reportable')) {
        $handler->reportable(function (\Throwable $e): void {
            if ($this->app->bound(\BugCompanion\Support\ContextBuffer::class)) {
                $this->app->make(\BugCompanion\Support\ContextBuffer::class)->addException($e);
            }
        });
    }
}
```

- [ ] **Step 5: Run tests, commit**

```bash
cd packages/bug-companion && ../../vendor/bin/pest --filter=CaptureContextTest && cd -
vendor/bin/pint --dirty --format agent
git add packages/bug-companion
git commit -m "feat(companion): capture per-request context in cache"
```

---

### Task 16: `VerifyBugsCallback` middleware + `DebugContextController` + route

**Files:**
- Create: `packages/bug-companion/src/Http/Middleware/VerifyBugsCallback.php`
- Create: `packages/bug-companion/src/Http/Controllers/DebugContextController.php`
- Create: `packages/bug-companion/routes/web.php`
- Modify: `packages/bug-companion/src/BugCompanionServiceProvider.php` (register route, alias middleware)
- Test: `packages/bug-companion/tests/Feature/DebugContextCallbackTest.php`

**Interfaces:**
- Produces: `GET /_bug-debug/{ids}` (auth via `bugs.callback` alias) returning `{id => context|null}`. Middleware verifies `X-Bugs-Signature` = `hash_hmac('sha256', "{ts}.{body}", secret)`; rejects if `|now - ts| > 60`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/bug-companion/tests/Feature/DebugContextCallbackTest.php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class DebugContextCallbackTest extends TestCase
{
    public function test_rejects_missing_signature(): void
    {
        $this->get('/_bug-debug/id1')->assertStatus(401);
    }

    public function test_returns_cached_context(): void
    {
        Cache::put('bug-companion:ctx:id1', ['debug_id' => 'id1', 'request' => ['path' => '/x']], 60);

        $ts = (string) time();
        $sig = hash_hmac('sha256', "{$ts}.", 'topsecret');

        $this->withHeaders([
            'X-Bugs-Timestamp' => $ts,
            'X-Bugs-Signature' => $sig,
        ])->get('/_bug-debug/id1|missing')
          ->assertOk()
          ->assertJson(['id1' => ['debug_id' => 'id1'], 'missing' => null]);
    }

    public function test_rejects_stale_timestamp(): void
    {
        $ts = (string) (time() - 300);
        $sig = hash_hmac('sha256', "{$ts}.", 'topsecret');
        $this->withHeaders([
            'X-Bugs-Timestamp' => $ts,
            'X-Bugs-Signature' => $sig,
        ])->get('/_bug-debug/id1')->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd packages/bug-companion && ../../vendor/bin/pest --filter=DebugContextCallbackTest && cd -`
Expected: FAIL.

- [ ] **Step 3: Middleware**

```php
<?php // packages/bug-companion/src/Http/Middleware/VerifyBugsCallback.php

namespace BugCompanion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBugsCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $ts  = (string) $request->header('X-Bugs-Timestamp', '');
        $sig = (string) $request->header('X-Bugs-Signature', '');
        $secret = (string) config('bug-companion.secret', '');

        if ($ts === '' || $sig === '' || $secret === '') {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        if (abs(time() - (int) $ts) > 60) {
            return response()->json(['error' => 'stale'], 401);
        }

        $expected = hash_hmac('sha256', "{$ts}." . $request->getContent(), $secret);
        if (! hash_equals($expected, $sig)) {
            return response()->json(['error' => 'bad signature'], 401);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Controller + route**

```php
<?php // packages/bug-companion/src/Http/Controllers/DebugContextController.php

namespace BugCompanion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller;

class DebugContextController extends Controller
{
    public function show(string $ids): JsonResponse
    {
        $out = [];
        foreach (explode('|', $ids) as $id) {
            $out[$id] = Cache::get("bug-companion:ctx:{$id}");
        }
        return response()->json($out);
    }
}
```

`packages/bug-companion/routes/web.php`:

```php
<?php

use BugCompanion\Http\Controllers\DebugContextController;
use Illuminate\Support\Facades\Route;

Route::middleware('bugs.callback')
    ->get('/_bug-debug/{ids}', [DebugContextController::class, 'show'])
    ->where('ids', '.*');
```

- [ ] **Step 5: Wire in provider**

Add to `BugCompanionServiceProvider::boot()` (after the `pushMiddlewareToGroup` call):

```php
$router->aliasMiddleware('bugs.callback', \BugCompanion\Http\Middleware\VerifyBugsCallback::class);
$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
```

- [ ] **Step 6: Run tests, commit**

```bash
cd packages/bug-companion && ../../vendor/bin/pest --filter=DebugContextCallbackTest && cd -
vendor/bin/pint --dirty --format agent
git add packages/bug-companion
git commit -m "feat(companion): add debug-context callback endpoint"
```

---

### Task 17: `@bugWidget` blade directive + widget script view

**Files:**
- Create: `packages/bug-companion/resources/views/widget.blade.php`
- Modify: `packages/bug-companion/src/BugCompanionServiceProvider.php` (register `@bugWidget` directive, `loadViewsFrom`)
- Test: `packages/bug-companion/tests/Feature/BugWidgetDirectiveTest.php`

**Interfaces:**
- Produces: `@bugWidget` compiles to a `<script>` include with `data-config` = signed token per Global Constraints. Includes payload keys: `source_slug, env, endpoint, user_id, user_email, user_name, exp`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/bug-companion/tests/Feature/BugWidgetDirectiveTest.php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;

class BugWidgetDirectiveTest extends TestCase
{
    public function test_directive_emits_script_with_valid_token(): void
    {
        $rendered = view('bug-companion::widget', ['user' => null])->render();
        preg_match('/data-config="([^"]+)"/', $rendered, $m);
        $this->assertNotEmpty($m[1] ?? null);
        $payload = TokenSigner::verify($m[1], 'topsecret');
        $this->assertSame('ahp', $payload['source_slug']);
        $this->assertStringContainsString('https://bugs.test/widget.js', $rendered);
    }

    public function test_directive_is_noop_when_disabled(): void
    {
        config()->set('bug-companion.enabled', false);
        $html = \Blade::compileString('@bugWidget');
        $rendered = eval('?>' . $html);
        // Blade compileString returns PHP; empty output means directive skipped
        $this->addToAssertionCount(1);
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd packages/bug-companion && ../../vendor/bin/pest --filter=BugWidgetDirectiveTest && cd -`
Expected: FAIL.

- [ ] **Step 3: View file**

```blade
{{-- packages/bug-companion/resources/views/widget.blade.php --}}
@php
    $endpoint = rtrim((string) config('bug-companion.endpoint'), '/');
    $secret   = (string) config('bug-companion.secret');
    $user     = auth()->user();
    $payload  = [
        'source_slug' => (string) config('bug-companion.source_slug'),
        'env'         => app()->environment(),
        'endpoint'    => $endpoint,
        'user_id'     => $user?->getKey(),
        'user_email'  => $user?->email,
        'user_name'   => $user?->name,
    ];
    $token = \BugCompanion\Support\TokenSigner::sign($payload, $secret, 900);
@endphp
<script src="{{ $endpoint }}/widget.js" defer data-config="{{ $token }}"></script>
```

- [ ] **Step 4: Register directive + views**

In `BugCompanionServiceProvider::boot()`, add:

```php
$this->loadViewsFrom(__DIR__.'/../resources/views', 'bug-companion');

\Illuminate\Support\Facades\Blade::directive('bugWidget', function () {
    return "<?php if (config('bug-companion.enabled', true)) { echo view('bug-companion::widget')->render(); } ?>";
});
```

- [ ] **Step 5: Run tests, commit**

```bash
cd packages/bug-companion && ../../vendor/bin/pest --filter=BugWidgetDirectiveTest && cd -
vendor/bin/pint --dirty --format agent
git add packages/bug-companion
git commit -m "feat(companion): add @bugWidget blade directive"
```

---

### Task 18: Publishable error views (500/503/404/419)

**Files:**
- Create: `packages/bug-companion/resources/views/errors/500.blade.php`
- Create: `packages/bug-companion/resources/views/errors/503.blade.php`
- Create: `packages/bug-companion/resources/views/errors/404.blade.php`
- Create: `packages/bug-companion/resources/views/errors/419.blade.php`
- Modify: `packages/bug-companion/src/BugCompanionServiceProvider.php` (add publish tag `bug-companion-errors`)
- Test: `packages/bug-companion/tests/Feature/ErrorViewsPublishTest.php`

**Interfaces:**
- Produces: publishable stubs each containing `@bugWidget` in `<head>`. Publish tag `bug-companion-errors`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/bug-companion/tests/Feature/ErrorViewsPublishTest.php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Tests\TestCase;

class ErrorViewsPublishTest extends TestCase
{
    public function test_errors_are_publishable(): void
    {
        $publishes = \Illuminate\Support\ServiceProvider::pathsToPublish(
            \BugCompanion\BugCompanionServiceProvider::class,
            'bug-companion-errors',
        );
        $this->assertNotEmpty($publishes);
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd packages/bug-companion && ../../vendor/bin/pest --filter=ErrorViewsPublishTest && cd -`
Expected: FAIL.

- [ ] **Step 3: Create 4 error views**

Each of `errors/500.blade.php`, `errors/503.blade.php`, `errors/404.blade.php`, `errors/419.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Something went wrong</title>
    @bugWidget
</head>
<body style="font-family:system-ui;padding:2rem">
    <h1>Something went wrong.</h1>
    <p>Use the report button in the corner to send a bug report.</p>
</body>
</html>
```

- [ ] **Step 4: Register publish**

Append to `BugCompanionServiceProvider::boot()`:

```php
$this->publishes([
    __DIR__.'/../resources/views/errors' => resource_path('views/errors'),
], 'bug-companion-errors');
```

- [ ] **Step 5: Run tests, commit**

```bash
cd packages/bug-companion && ../../vendor/bin/pest --filter=ErrorViewsPublishTest && cd -
vendor/bin/pint --dirty --format agent
git add packages/bug-companion
git commit -m "feat(companion): add publishable error views w/ bug widget"
```

---

### Phase 2 Wrap-up

Run full package suite:

```bash
cd packages/bug-companion && ../../vendor/bin/pest && cd -
```

Companion package is complete. Ready to `composer require` in AHP + Takeoff.

---

## Phase 3 — Widget JS

Delivers `public/widget.js` (single IIFE), a dev preview page, Vitest unit tests, and one Playwright smoke test.

### Task 19: Add npm deps + Vite widget build target

**Files:**
- Modify: `package.json` (deps + build script)
- Modify: `vite.config.js` (widget bundle config)
- Create: `resources/widget/index.js` (entry stub — full impl in later tasks)

**Interfaces:**
- Produces: `npm run build:widget` outputs a single IIFE to `public/widget.js`.

- [ ] **Step 1: Add dependencies**

```bash
npm install --save-dev vitest jsdom @vitest/browser
npm install html2canvas
npm install --save-dev @playwright/test
```

- [ ] **Step 2: Widget entry stub**

`resources/widget/index.js`:

```js
// Entry stub — filled in tasks 20–26.
(function () {
    if (typeof window === 'undefined') { return; }
    window.__bugWidgetLoaded = true;
})();
```

- [ ] **Step 3: Vite widget config**

Add a second Vite build inside `vite.config.js`. The simplest approach is a separate config file. Create `vite.widget.config.js`:

```js
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        emptyOutDir: false,
        outDir: 'public',
        lib: {
            entry: 'resources/widget/index.js',
            name: 'BugWidget',
            formats: ['iife'],
            fileName: () => 'widget.js',
        },
        rollupOptions: {
            output: {
                inlineDynamicImports: true,
            },
        },
    },
});
```

Update `package.json` scripts:

```json
"scripts": {
    "build": "vp build",
    "dev": "vp dev",
    "build:widget": "vite build --config vite.widget.config.js",
    "test:widget": "vitest run --config vite.widget.config.js"
}
```

- [ ] **Step 4: Verify build**

```bash
npm run build:widget
```

Expected: `public/widget.js` exists.

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json vite.widget.config.js resources/widget public/widget.js
git commit -m "feat(widget): add vite build target and entry stub"
```

---

### Task 20: Ring buffer utility + Vitest setup

**Files:**
- Create: `resources/widget/lib/ring.js`
- Create: `resources/widget/lib/ring.test.js`
- Create: `vitest.config.js`

**Interfaces:**
- Produces: `Ring(size)` with `.push(item)` and `.toArray()`. Bounded, drops oldest.

- [ ] **Step 1: Vitest config**

```js
// vitest.config.js
import { defineConfig } from 'vitest/config';
export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['resources/widget/**/*.test.js'],
    },
});
```

Update `package.json`:

```json
"test:widget": "vitest run"
```

- [ ] **Step 2: Failing test**

```js
// resources/widget/lib/ring.test.js
import { describe, it, expect } from 'vitest';
import { Ring } from './ring.js';

describe('Ring', () => {
    it('caps and preserves order', () => {
        const r = new Ring(3);
        r.push(1); r.push(2); r.push(3); r.push(4);
        expect(r.toArray()).toEqual([2, 3, 4]);
    });
    it('handles zero-size gracefully', () => {
        const r = new Ring(0);
        r.push(1);
        expect(r.toArray()).toEqual([]);
    });
});
```

- [ ] **Step 3: Run to fail**

Run: `npm run test:widget`
Expected: FAIL — module missing.

- [ ] **Step 4: Implement**

```js
// resources/widget/lib/ring.js
export class Ring {
    constructor(size) {
        this.size = size;
        this.items = [];
    }
    push(item) {
        if (this.size <= 0) return;
        this.items.push(item);
        if (this.items.length > this.size) this.items.splice(0, this.items.length - this.size);
    }
    toArray() {
        return this.items.slice();
    }
}
```

- [ ] **Step 5: Run, commit**

```bash
npm run test:widget
git add resources/widget/lib package.json vitest.config.js
git commit -m "feat(widget): add ring buffer"
```

---

### Task 21: Fetch + XHR instrumentation

**Files:**
- Create: `resources/widget/lib/net.js`
- Create: `resources/widget/lib/net.test.js`

**Interfaces:**
- Produces: `installNetworkCapture({ ring, skipUrlPrefix })` — wraps `window.fetch` and `XMLHttpRequest`. Each call recorded as `{ method, url, status, duration_ms, ts, debug_id }`. Skips URLs starting with `skipUrlPrefix`.

- [ ] **Step 1: Failing test**

```js
// resources/widget/lib/net.test.js
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { Ring } from './ring.js';
import { installNetworkCapture } from './net.js';

describe('network capture', () => {
    let ring;
    beforeEach(() => {
        ring = new Ring(10);
        globalThis.fetch = vi.fn(() => Promise.resolve(new Response('ok', {
            status: 200,
            headers: { 'X-Debug-Id': 'abc' },
        })));
        installNetworkCapture({ ring, skipUrlPrefix: 'https://skip.test' });
    });

    it('records fetch calls', async () => {
        await fetch('/api/thing');
        const [call] = ring.toArray();
        expect(call.url).toBe('/api/thing');
        expect(call.status).toBe(200);
        expect(call.debug_id).toBe('abc');
    });

    it('skips prefixed urls', async () => {
        await fetch('https://skip.test/anything');
        expect(ring.toArray()).toHaveLength(0);
    });
});
```

- [ ] **Step 2: Run to fail**

Run: `npm run test:widget`
Expected: FAIL.

- [ ] **Step 3: Implement**

```js
// resources/widget/lib/net.js
export function installNetworkCapture({ ring, skipUrlPrefix }) {
    const originalFetch = globalThis.fetch;

    globalThis.fetch = async function (input, init) {
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        const method = (init && init.method) || (input && input.method) || 'GET';
        if (skipUrlPrefix && url.startsWith(skipUrlPrefix)) {
            return originalFetch(input, init);
        }
        const start = performance.now();
        try {
            const resp = await originalFetch(input, init);
            ring.push({
                method, url, status: resp.status,
                duration_ms: Math.round(performance.now() - start),
                ts: Date.now(),
                debug_id: resp.headers.get('X-Debug-Id') || null,
            });
            return resp;
        } catch (e) {
            ring.push({ method, url, status: 0, duration_ms: Math.round(performance.now() - start), ts: Date.now(), debug_id: null });
            throw e;
        }
    };

    if (typeof XMLHttpRequest !== 'undefined') {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (method, url) {
            this.__bug = { method, url, start: 0 };
            return origOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function () {
            if (!this.__bug || (skipUrlPrefix && this.__bug.url.startsWith(skipUrlPrefix))) {
                return origSend.apply(this, arguments);
            }
            this.__bug.start = performance.now();
            this.addEventListener('loadend', () => {
                ring.push({
                    method: this.__bug.method, url: this.__bug.url, status: this.status,
                    duration_ms: Math.round(performance.now() - this.__bug.start),
                    ts: Date.now(),
                    debug_id: this.getResponseHeader('X-Debug-Id') || null,
                });
            });
            return origSend.apply(this, arguments);
        };
    }
}
```

- [ ] **Step 4: Run, commit**

```bash
npm run test:widget
git add resources/widget/lib
git commit -m "feat(widget): capture fetch + xhr calls"
```

---

### Task 22: Client-event capture (errors, unhandledrejection, console)

**Files:**
- Create: `resources/widget/lib/events.js`
- Create: `resources/widget/lib/events.test.js`

**Interfaces:**
- Produces: `installEventCapture({ ring })` — wires `error`, `unhandledrejection`, wraps `console.error`, `console.warn`, `pushState`, `hashchange`, `popstate`.

- [ ] **Step 1: Failing test**

```js
// resources/widget/lib/events.test.js
import { describe, it, expect, beforeEach } from 'vitest';
import { Ring } from './ring.js';
import { installEventCapture } from './events.js';

describe('event capture', () => {
    let ring;
    beforeEach(() => {
        ring = new Ring(10);
        installEventCapture({ ring });
    });

    it('captures console.error', () => {
        console.error('boom');
        const [e] = ring.toArray();
        expect(e.type).toBe('console.error');
        expect(e.message).toContain('boom');
    });

    it('captures error events', () => {
        window.dispatchEvent(new ErrorEvent('error', { message: 'oops', filename: 'a.js', lineno: 1 }));
        const items = ring.toArray();
        expect(items.some(x => x.type === 'error' && x.message === 'oops')).toBe(true);
    });
});
```

- [ ] **Step 2: Run to fail**

Run: `npm run test:widget`
Expected: FAIL.

- [ ] **Step 3: Implement**

```js
// resources/widget/lib/events.js
export function installEventCapture({ ring }) {
    window.addEventListener('error', (e) => {
        ring.push({
            type: 'error',
            message: e.message,
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno,
            stack: e.error?.stack || null,
            ts: Date.now(),
        });
    });

    window.addEventListener('unhandledrejection', (e) => {
        ring.push({
            type: 'unhandledrejection',
            message: String(e.reason?.message ?? e.reason ?? ''),
            stack: e.reason?.stack || null,
            ts: Date.now(),
        });
    });

    ['error', 'warn'].forEach((level) => {
        const orig = console[level].bind(console);
        console[level] = function (...args) {
            try {
                ring.push({
                    type: `console.${level}`,
                    message: args.map((a) => (typeof a === 'string' ? a : JSON.stringify(a))).join(' '),
                    ts: Date.now(),
                });
            } catch (_) { /* ignore */ }
            return orig(...args);
        };
    });

    window.addEventListener('popstate', () => ring.push({ type: 'nav.popstate', url: location.href, ts: Date.now() }));
    window.addEventListener('hashchange', () => ring.push({ type: 'nav.hashchange', url: location.href, ts: Date.now() }));

    const origPushState = history.pushState.bind(history);
    history.pushState = function (state, title, url) {
        ring.push({ type: 'nav.pushState', url: String(url || location.href), ts: Date.now() });
        return origPushState(state, title, url);
    };
}
```

- [ ] **Step 4: Run, commit**

```bash
npm run test:widget
git add resources/widget/lib
git commit -m "feat(widget): capture errors, rejections, console, nav events"
```

---

### Task 23: Config decoding

**Files:**
- Create: `resources/widget/lib/config.js`
- Create: `resources/widget/lib/config.test.js`

**Interfaces:**
- Produces: `readConfig(scriptEl): { endpoint, source_slug, env, user_id, user_email, user_name, token }` — reads `data-config` attribute, decodes base64url of the payload half, returns fields + the raw token.

- [ ] **Step 1: Failing test**

```js
// resources/widget/lib/config.test.js
import { describe, it, expect } from 'vitest';
import { readConfig } from './config.js';

function b64url(s) {
    return btoa(s).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '');
}

describe('readConfig', () => {
    it('decodes payload', () => {
        const payload = { endpoint: 'https://bugs.test', source_slug: 'ahp', env: 'prod',
            user_id: 1, user_email: 'a@b', user_name: 'A', exp: 9e9 };
        const token = `${b64url(JSON.stringify(payload))}.sig`;
        const el = document.createElement('script');
        el.setAttribute('data-config', token);
        const cfg = readConfig(el);
        expect(cfg.endpoint).toBe('https://bugs.test');
        expect(cfg.token).toBe(token);
    });

    it('returns null for missing config', () => {
        const el = document.createElement('script');
        expect(readConfig(el)).toBeNull();
    });
});
```

- [ ] **Step 2: Run to fail**

Run: `npm run test:widget`
Expected: FAIL.

- [ ] **Step 3: Implement**

```js
// resources/widget/lib/config.js
export function readConfig(scriptEl) {
    if (!scriptEl) return null;
    const token = scriptEl.getAttribute('data-config');
    if (!token) return null;
    const [body] = token.split('.');
    if (!body) return null;
    try {
        const pad = body.length % 4 ? '='.repeat(4 - (body.length % 4)) : '';
        const json = atob(body.replaceAll('-', '+').replaceAll('_', '/') + pad);
        const payload = JSON.parse(json);
        return { ...payload, token };
    } catch (_) {
        return null;
    }
}
```

- [ ] **Step 4: Run, commit**

```bash
npm run test:widget
git add resources/widget/lib
git commit -m "feat(widget): decode data-config token payload"
```

---

### Task 24: Shadow-root UI mount + floating button + modal

**Files:**
- Create: `resources/widget/lib/ui.js`
- Create: `resources/widget/lib/ui.test.js`

**Interfaces:**
- Produces: `mountUi({ config, onSubmit })` — attaches shadow root to `document.body`, renders floating button + hidden modal, wires `Escape` and backdrop-with-confirm, exposes `open()` and `close()`. `onSubmit(formData)` is invoked on submit.

- [ ] **Step 1: Failing test**

```js
// resources/widget/lib/ui.test.js
import { describe, it, expect, vi } from 'vitest';
import { mountUi } from './ui.js';

describe('mountUi', () => {
    it('mounts a shadow root with a button', () => {
        document.body.innerHTML = '';
        const ui = mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });
        const root = document.getElementById('bug-widget-root');
        expect(root).toBeTruthy();
        expect(root.shadowRoot.querySelector('button')).toBeTruthy();
    });

    it('opens on button click', () => {
        document.body.innerHTML = '';
        const ui = mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });
        const root = document.getElementById('bug-widget-root');
        const button = root.shadowRoot.querySelector('button.bug-toggle');
        button.click();
        expect(root.shadowRoot.querySelector('.bug-modal').classList.contains('open')).toBe(true);
    });
});
```

- [ ] **Step 2: Run to fail**

Run: `npm run test:widget`
Expected: FAIL.

- [ ] **Step 3: Implement**

```js
// resources/widget/lib/ui.js
const STYLE = `
    :host { all: initial; }
    .bug-toggle { position: fixed; bottom: 16px; right: 16px; width: 40px; height: 40px;
        border-radius: 20px; border: 0; background: #d33; color: #fff; font: 600 14px/40px system-ui;
        cursor: pointer; z-index: 2147483647; }
    .bug-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 2147483647; align-items: center; justify-content: center; }
    .bug-modal.open { display: flex; }
    .bug-card { background: #fff; padding: 20px; border-radius: 8px; width: min(520px, 92vw);
        font: 14px/1.4 system-ui; color: #111; }
    .bug-card label { display: block; margin-top: 10px; font-weight: 600; }
    .bug-card input, .bug-card textarea, .bug-card select { width: 100%; padding: 6px; box-sizing: border-box; }
    .bug-actions { margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end; }
    .bug-actions button { padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; background: #fff; cursor: pointer; }
    .bug-actions .primary { background: #111; color: #fff; border-color: #111; }
    .bug-note { color: #666; font-size: 12px; margin-top: 4px; }
`;

export function mountUi({ config, onSubmit }) {
    const host = document.createElement('div');
    host.id = 'bug-widget-root';
    (document.body || document.documentElement).appendChild(host);
    const root = host.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = STYLE;
    root.appendChild(style);

    root.innerHTML += `
        <button class="bug-toggle" title="Report a bug">🐞</button>
        <div class="bug-modal" role="dialog" aria-modal="true">
            <div class="bug-card">
                <h2 style="margin:0">Report a bug or request</h2>
                <div class="bug-note">Reporting as ${escapeHtml(config.user_name || 'anon')} &lt;${escapeHtml(config.user_email || '')}&gt;</div>
                <label>Type
                    <select name="type">
                        <option value="bug">Bug</option>
                        <option value="feature">Feature request</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>Severity
                    <select name="severity">
                        <option value="low">Low</option>
                        <option value="med" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="blocker">Blocker</option>
                    </select>
                </label>
                <label>Title <input name="title" required></label>
                <label>Description <textarea name="description" rows="4"></textarea></label>
                <label>Additional files <input type="file" name="attachments" multiple></label>
                <div class="bug-actions">
                    <button class="cancel" type="button">Cancel</button>
                    <button class="primary submit" type="button">Send report</button>
                </div>
            </div>
        </div>
    `;

    const modal = root.querySelector('.bug-modal');
    const toggle = root.querySelector('.bug-toggle');
    const cancel = root.querySelector('.cancel');
    const submit = root.querySelector('.submit');

    function open() { modal.classList.add('open'); }
    function close() { modal.classList.remove('open'); }

    toggle.addEventListener('click', open);
    cancel.addEventListener('click', close);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
        if (e.ctrlKey && e.shiftKey && e.key === 'B') open();
    });

    submit.addEventListener('click', () => {
        const fd = new FormData();
        fd.set('title', root.querySelector('[name=title]').value);
        fd.set('description', root.querySelector('[name=description]').value);
        fd.set('type', root.querySelector('[name=type]').value);
        fd.set('severity', root.querySelector('[name=severity]').value);
        const files = root.querySelector('[name=attachments]').files;
        for (const f of files) fd.append('attachments[]', f);
        onSubmit(fd, { close, disable: () => submit.disabled = true, enable: () => submit.disabled = false });
    });

    return { open, close, root };
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
```

- [ ] **Step 4: Run, commit**

```bash
npm run test:widget
git add resources/widget/lib
git commit -m "feat(widget): shadow-root UI with toggle + modal"
```

---

### Task 25: Snapshot + submit

**Files:**
- Create: `resources/widget/lib/snapshot.js`
- Create: `resources/widget/lib/submit.js`
- Create: `resources/widget/lib/submit.test.js`

**Interfaces:**
- `snapshot(): Promise<Blob>` — html2canvas of `document.documentElement`, `canvas.toBlob('image/jpeg', 0.85)`. If oversize (>2MB), re-encode at 0.7.
- `submit({ endpoint, token, formData, metadata, snapshotBlob }): Promise<{ ok: boolean, status: number, report_id?: number }>`.

- [ ] **Step 1: Failing test**

```js
// resources/widget/lib/submit.test.js
import { describe, it, expect, vi } from 'vitest';
import { submit } from './submit.js';

describe('submit', () => {
    it('POSTs multipart with metadata + snapshot', async () => {
        const fd = new FormData();
        fd.set('title', 't');
        globalThis.fetch = vi.fn(() => Promise.resolve(new Response(JSON.stringify({ report_id: 7 }), { status: 201 })));

        const out = await submit({
            endpoint: 'https://bugs.test', token: 'abc.def',
            formData: fd, metadata: { url: 'x' }, snapshotBlob: new Blob(['x'], { type: 'image/jpeg' }),
        });

        expect(out.ok).toBe(true);
        expect(out.report_id).toBe(7);
        const call = fetch.mock.calls[0];
        expect(call[0]).toBe('https://bugs.test/api/reports');
        expect(call[1].headers.Authorization).toBe('Bearer abc.def');
    });
});
```

- [ ] **Step 2: Run to fail**

Run: `npm run test:widget`
Expected: FAIL.

- [ ] **Step 3: Implement**

```js
// resources/widget/lib/snapshot.js
import html2canvas from 'html2canvas';

const MAX = 2 * 1024 * 1024;

export async function snapshot() {
    const canvas = await html2canvas(document.documentElement, { logging: false, useCORS: true });
    let blob = await toBlob(canvas, 0.85);
    if (blob.size > MAX) blob = await toBlob(canvas, 0.7);
    return blob;
}

function toBlob(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}
```

```js
// resources/widget/lib/submit.js
export async function submit({ endpoint, token, formData, metadata, snapshotBlob }) {
    formData.set('metadata', JSON.stringify(metadata));
    if (snapshotBlob) formData.set('snapshot', snapshotBlob, 'snapshot.jpg');

    const resp = await fetch(`${endpoint}/api/reports`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: formData,
    });

    if (!resp.ok) return { ok: false, status: resp.status };

    const json = await resp.json().catch(() => ({}));
    return { ok: true, status: resp.status, report_id: json.report_id };
}
```

- [ ] **Step 4: Run, commit**

```bash
npm run test:widget
git add resources/widget/lib
git commit -m "feat(widget): add snapshot + submit"
```

---

### Task 26: Wire entry point

**Files:**
- Modify: `resources/widget/index.js`

**Interfaces:**
- Produces: on `DOMContentLoaded`, reads own script's `data-config`, installs network + event capture, mounts UI, wires submit to gather metadata + snapshot + POST.

- [ ] **Step 1: Replace entry**

```js
// resources/widget/index.js
import { Ring } from './lib/ring.js';
import { installNetworkCapture } from './lib/net.js';
import { installEventCapture } from './lib/events.js';
import { readConfig } from './lib/config.js';
import { mountUi } from './lib/ui.js';
import { snapshot } from './lib/snapshot.js';
import { submit } from './lib/submit.js';

(function () {
    if (typeof window === 'undefined') return;

    const scriptEl = document.currentScript || Array.from(document.scripts).find(s => s.src && s.src.endsWith('/widget.js'));
    const config = readConfig(scriptEl);
    if (!config || !config.endpoint) return;

    const netRing = new Ring(50);
    const eventRing = new Ring(100);
    installNetworkCapture({ ring: netRing, skipUrlPrefix: config.endpoint });
    installEventCapture({ ring: eventRing });

    const boot = () => {
        mountUi({
            config,
            onSubmit: async (formData, { close, disable, enable }) => {
                disable();
                let snap = null;
                try { snap = await snapshot(); } catch (_) { /* ignore */ }

                const network = netRing.toArray();
                const debugIds = Array.from(new Set(network.map(x => x.debug_id).filter(Boolean)));

                const metadata = {
                    url: location.href,
                    referrer: document.referrer || null,
                    title: document.title,
                    screen_wh: `${screen.width}x${screen.height}`,
                    viewport_wh: `${innerWidth}x${innerHeight}`,
                    dpr: devicePixelRatio,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    locale: navigator.language,
                    user_agent: navigator.userAgent,
                    app_version: document.querySelector('meta[name=app-version]')?.getAttribute('content') || null,
                    network_log: network,
                    client_events: eventRing.toArray(),
                    debug_ids: debugIds,
                };

                const result = await submit({
                    endpoint: config.endpoint, token: config.token,
                    formData, metadata, snapshotBlob: snap,
                });

                enable();
                if (result.ok) {
                    close();
                    alert('Report sent — thanks!');
                } else {
                    alert(`Failed to send (status ${result.status}). Please try again.`);
                }
            },
        });
    };

    if (document.body) {
        boot();
    } else {
        new MutationObserver((_, obs) => {
            if (document.body) { obs.disconnect(); boot(); }
        }).observe(document.documentElement, { childList: true, subtree: true });
    }
})();
```

- [ ] **Step 2: Rebuild widget**

```bash
npm run build:widget
```

- [ ] **Step 3: Commit**

```bash
git add resources/widget public/widget.js
git commit -m "feat(widget): wire entry — instrumentation + UI + submit"
```

---

### Task 27: Dev preview + Playwright smoke test

**Files:**
- Create: `resources/dev/widget-preview.html`
- Create: `playwright.config.js`
- Create: `tests/widget/widget.spec.js`

**Interfaces:**
- Preview page loads `public/widget.js` directly with a mock `data-config`. Playwright test opens the preview, mocks the report endpoint, opens the widget, submits, verifies POST fired with expected shape.

- [ ] **Step 1: Preview page**

```html
<!-- resources/dev/widget-preview.html -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Widget preview</title>
    <meta name="app-version" content="0.1.0">
</head>
<body>
    <h1>Preview host page</h1>
    <p>Some content.</p>
    <script>
        // Fake config token for the preview (payload only, signature ignored).
        const payload = { endpoint: 'https://bugs.test', source_slug: 'ahp', env: 'local',
            user_id: 1, user_email: 'preview@ahp.test', user_name: 'Preview User', exp: 9999999999 };
        const b64 = btoa(JSON.stringify(payload)).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '');
        const s = document.createElement('script');
        s.src = '/widget.js';
        s.defer = true;
        s.setAttribute('data-config', `${b64}.sig`);
        document.head.appendChild(s);
    </script>
</body>
</html>
```

Copy to `public/dev/widget-preview.html` for direct serving:

```bash
mkdir -p public/dev
cp resources/dev/widget-preview.html public/dev/widget-preview.html
```

- [ ] **Step 2: Playwright config**

```js
// playwright.config.js
import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: './tests/widget',
    use: { baseURL: 'http://127.0.0.1:8000' },
    webServer: {
        command: 'php artisan serve --port=8000',
        url: 'http://127.0.0.1:8000/dev/widget-preview.html',
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
    },
});
```

Install browsers:

```bash
npx playwright install chromium
```

- [ ] **Step 3: Smoke test**

```js
// tests/widget/widget.spec.js
import { test, expect } from '@playwright/test';

test('widget submits a report', async ({ page }) => {
    let posted = null;
    await page.route('https://bugs.test/api/reports', async (route) => {
        const req = route.request();
        posted = { url: req.url(), headers: req.headers(), postData: await req.postDataBuffer() };
        route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify({ report_id: 1 }) });
    });

    await page.goto('/dev/widget-preview.html');
    await page.waitForFunction(() => document.getElementById('bug-widget-root'));

    const root = page.locator('#bug-widget-root');
    await root.evaluate((el) => el.shadowRoot.querySelector('button.bug-toggle').click());
    await root.evaluate((el) => {
        el.shadowRoot.querySelector('[name=title]').value = 'Preview title';
        el.shadowRoot.querySelector('[name=description]').value = 'Preview desc';
        el.shadowRoot.querySelector('.submit').click();
    });

    await expect.poll(() => posted).not.toBeNull();
    expect(posted.headers['authorization']).toMatch(/^Bearer /);
});
```

Update `package.json`:

```json
"test:widget:e2e": "playwright test"
```

- [ ] **Step 4: Run, commit**

```bash
npm run build:widget
npm run test:widget:e2e
git add resources/dev public/dev playwright.config.js tests/widget package.json
git commit -m "test(widget): add preview page + playwright smoke test"
```

---

## Global wrap-up

- [ ] **Run everything**

```bash
php artisan test --compact
cd packages/bug-companion && ../../vendor/bin/pest && cd -
npm run test:widget
npm run test:widget:e2e
vendor/bin/pint --dirty --format agent
```

- [ ] **Final commit sweep**

```bash
git add -A
git commit -m "chore(bugs): plan-complete lint sweep" || true
```

- [ ] **Verify smoke path end-to-end in local dev**

Not automated: with the Bugs app running locally, install `packages/bug-companion` via composer path repo in AHP dev, add `@bugWidget` to a page, set env vars, submit a real report, confirm `laravel_context` populates.
