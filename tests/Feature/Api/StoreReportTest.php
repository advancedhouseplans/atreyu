<?php

// tests/Feature/Api/StoreReportTest.php

use App\Models\Project;
use App\Models\Report;
use App\Models\ReporterInstallation;
use App\Models\User;
use App\Notifications\ReportReceived;
use App\Services\SourceContextFetcher;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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
    $b64 = fn ($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
    $body = $b64(json_encode($payload));
    $sig = $b64(hash_hmac('sha256', $body, $secret, true));

    return "Bearer {$body}.{$sig}";
}

it('rejects unauthenticated submission', function () {
    $this->postJson('/api/reports', [])->assertStatus(401);
});

it('creates a report from valid submission', function () {
    Notification::fake();
    $recipient = User::factory()->create();
    $firstDebugId = '11111111-1111-4111-8111-111111111111';
    $secondDebugId = '22222222-2222-4222-8222-222222222222';
    $this->mock(SourceContextFetcher::class)
        ->shouldReceive('fetch')->once()
        ->with('ahp', [$firstDebugId, $secondDebugId])
        ->andReturn([$firstDebugId => ['route_name' => 'x'], $secondDebugId => null]);

    $metadata = json_encode([
        'url' => 'https://ahp.test/plans/1', 'referrer' => null, 'title' => 'plan',
        'screen_wh' => '1920x1080', 'viewport_wh' => '1440x900', 'dpr' => 2.0,
        'timezone' => 'America/New_York', 'locale' => 'en-US',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'app_version' => '1.2.3',
        'network_log' => [['method' => 'GET', 'url' => '/x', 'status' => 200, 'debug_id' => $firstDebugId]],
        'client_events' => [['type' => 'error', 'message' => 'boom']],
        'debug_ids' => [$firstDebugId, $secondDebugId],
    ]);

    $resp = $this->withHeader('Authorization', bearer())->post('/api/reports', [
        'title' => 'Broken thing',
        'description' => 'It broke',
        'type' => 'bug',
        'severity' => 'high',
        'metadata' => $metadata,
        'snapshot' => File::image('snap.jpg', 100, 100),
        'attachments' => [File::create('note.txt', 5)],
    ]);

    $resp->assertCreated()->assertJsonStructure(['report_id']);

    $r = Report::first();
    expect($r->source_slug)->toBe('ahp')
        ->and($r->reporter_email)->toBe('reporter@ahp.test')
        ->and($r->title)->toBe('Broken thing')
        ->and($r->browser)->toBe('Chrome')
        ->and($r->laravel_context)->toBe([
            ['id' => $firstDebugId, 'context' => ['route_name' => 'x']],
            ['id' => $secondDebugId, 'context' => null],
        ])
        ->and($r->attachments)->toHaveCount(2);
    Notification::assertSentTo($recipient, ReportReceived::class);
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

it('attributes a report to its registered installation and project', function () {
    $project = Project::factory()->create(['slug' => 'clara']);
    $installation = ReporterInstallation::factory()->for($project)->create();
    $debugId = '11111111-1111-4111-8111-111111111111';
    $this->mock(SourceContextFetcher::class)
        ->shouldReceive('fetch')
        ->once()
        ->with(Mockery::on(fn ($value): bool => $value instanceof ReporterInstallation && $value->is($installation)), [$debugId])
        ->andReturn([]);
    $authorization = bearer([
        'source_slug' => null,
        'key_id' => $installation->key_id,
        'endpoint' => 'https://clara.test',
        'user_email' => null,
        'user_name' => null,
    ], $installation->secret);

    $this->withHeader('Authorization', $authorization)->post('/api/reports', [
        'title' => 'Anonymous problem',
        'description' => 'Something went wrong',
        'type' => 'bug',
        'severity' => 'low',
        'metadata' => json_encode(['debug_ids' => [$debugId]], JSON_THROW_ON_ERROR),
    ])->assertCreated();

    $report = Report::sole();
    expect($report->source_slug)->toBe('clara')
        ->and($report->reporter_installation_id)->toBe($installation->id)
        ->and($report->reporter_email)->toBeNull();
});

it('groups matching bugs as occurrences while keeping feature requests independent', function () {
    $this->mock(SourceContextFetcher::class)->shouldReceive('fetch')->times(4)->andReturn([]);
    $auth = ['Authorization' => bearer()];

    foreach (['https://ahp.test/plans/123', 'https://ahp.test/plans/456'] as $url) {
        $this->withHeaders($auth)->post('/api/reports', [
            'title' => 'Save button crashes for plan 123',
            'description' => 'It broke',
            'type' => 'bug',
            'severity' => 'high',
            'metadata' => json_encode(['url' => $url]),
        ])->assertCreated();
    }

    foreach (range(1, 2) as $index) {
        $this->withHeaders($auth)->post('/api/reports', [
            'title' => 'Add a new export',
            'description' => 'Feature idea '.$index,
            'type' => 'feature',
            'severity' => 'low',
            'metadata' => json_encode(['url' => 'https://ahp.test/plans']),
        ])->assertCreated();
    }

    $bugReports = Report::where('type', 'bug')->orderBy('id')->get();
    expect($bugReports)->toHaveCount(2)
        ->and($bugReports[0]->occurrence_parent_id)->toBeNull()
        ->and($bugReports[1]->occurrence_parent_id)->toBe($bugReports[0]->id)
        ->and(Report::where('type', 'feature')->whereNull('occurrence_parent_id')->count())->toBe(2);
});
