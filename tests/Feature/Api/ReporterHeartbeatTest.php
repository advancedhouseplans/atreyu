<?php

use App\Models\Project;
use App\Models\ReporterInstallation;

function heartbeatHeaders(ReporterInstallation $installation, array $payload, ?string $secret = null): array
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) time();
    $message = implode("\n", [$timestamp, 'POST', '/api/reporter/heartbeat', hash('sha256', $body)]);

    return [
        'X-Atreyu-Key' => $installation->key_id,
        'X-Atreyu-Timestamp' => $timestamp,
        'X-Atreyu-Signature' => hash_hmac('sha256', $message, $secret ?? $installation->secret),
    ];
}

function heartbeatPayload(array $overrides = []): array
{
    return array_merge([
        'endpoint' => 'https://clara.test',
        'app_name' => 'Clara',
        'environment' => 'local',
        'package_version' => '0.2.0',
        'app_version' => 'abc123',
        'laravel_version' => '13.0.0',
        'php_version' => '8.4.0',
    ], $overrides);
}

it('registers a signed reporter heartbeat', function () {
    $project = Project::factory()->create(['slug' => 'clara']);
    $installation = ReporterInstallation::factory()->for($project)->create(['endpoint' => null]);
    $payload = heartbeatPayload();

    $this->withHeaders(heartbeatHeaders($installation, $payload))
        ->postJson(route('api.reporter.heartbeat'), $payload)
        ->assertOk()
        ->assertJson([
            'project' => 'clara',
            'installation' => $installation->key_id,
            'context_ttl' => 3600,
        ]);

    $installation->refresh();
    expect($installation->endpoint)->toBe('https://clara.test')
        ->and($installation->allowed_origins)->toBe(['https://clara.test'])
        ->and($installation->package_version)->toBe('0.2.0')
        ->and($installation->last_seen_at)->not->toBeNull();
});

it('rejects an invalid signature', function () {
    $installation = ReporterInstallation::factory()->create();
    $payload = heartbeatPayload();

    $this->withHeaders(heartbeatHeaders($installation, $payload, 'wrong-secret'))
        ->postJson(route('api.reporter.heartbeat'), $payload)
        ->assertUnauthorized();
});

it('rejects a revoked installation', function () {
    $installation = ReporterInstallation::factory()->create(['revoked_at' => now()]);
    $payload = heartbeatPayload();

    $this->withHeaders(heartbeatHeaders($installation, $payload))
        ->postJson(route('api.reporter.heartbeat'), $payload)
        ->assertUnauthorized();
});

it('only accepts an http callback endpoint', function () {
    $installation = ReporterInstallation::factory()->create();
    $payload = heartbeatPayload(['endpoint' => 'file:///etc/passwd']);

    $this->withHeaders(heartbeatHeaders($installation, $payload))
        ->postJson(route('api.reporter.heartbeat'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('automatically allows the heartbeat-discovered origin for report submissions', function () {
    ReporterInstallation::factory()->create([
        'allowed_origins' => ['https://clara.test'],
        'last_seen_at' => now(),
    ]);

    $this->withHeaders([
        'Origin' => 'https://clara.test',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Authorization, Content-Type',
    ])->options('/api/reports')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://clara.test');
});

it('does not allow an unregistered origin', function () {
    $response = $this->withHeaders([
        'Origin' => 'https://untrusted.test',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Authorization, Content-Type',
    ])->options('/api/reports');

    expect($response->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('https://untrusted.test');
});
