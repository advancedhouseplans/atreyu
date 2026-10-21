<?php

use App\Models\Project;
use App\Models\ReporterInstallation;
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
    Http::fake(function ($request) {
        return Http::response(['contexts' => ['id1' => ['route_name' => 'x'], 'id2' => null]], 200);
    });

    $out = app(SourceContextFetcher::class)->fetch('ahp', ['id1', 'id2']);
    expect($out)->toBe(['id1' => ['route_name' => 'x'], 'id2' => null]);

    Http::assertSent(function ($request) {
        $body = (string) $request->body();
        $timestamp = $request->header('X-Atreyu-Timestamp')[0];
        $expected = hash_hmac('sha256', implode("\n", [
            $timestamp,
            'POST',
            '/_atreyu/context',
            hash('sha256', $body),
        ]), 'topsecret');

        return $request->method() === 'POST'
            && $request->hasHeader('X-Atreyu-Signature', $expected)
            && str_ends_with((string) $request->url(), '/_atreyu/context')
            && $request->data() === ['ids' => ['id1', 'id2']];
    });
});

it('chunks context requests to the package limit', function () {
    Http::fake(['*' => Http::response(['contexts' => []])]);
    $ids = array_map(fn (int $number): string => "00000000-0000-4000-8000-0000000000{$number}", range(10, 39));

    app(SourceContextFetcher::class)->fetch('ahp', $ids);

    Http::assertSentCount(2);
});

it('returns [] on 5xx', function () {
    Http::fake(['*' => Http::response('', 500)]);
    expect(app(SourceContextFetcher::class)->fetch('ahp', ['a']))->toBe([]);
});

it('returns [] on unknown source', function () {
    expect(app(SourceContextFetcher::class)->fetch('ghost', ['a']))->toBe([]);
});

it('uses the installation callback and encrypted secret', function () {
    $project = Project::factory()->create(['slug' => 'clara']);
    $installation = ReporterInstallation::factory()->for($project)->create([
        'endpoint' => 'https://clara.test',
        'secret' => 'installation-secret',
    ]);
    Http::fake(['*' => Http::response(['contexts' => ['id1' => ['route_name' => 'home']]])]);

    $contexts = app(SourceContextFetcher::class)->fetch($installation, ['id1']);

    expect($contexts)->toBe(['id1' => ['route_name' => 'home']]);
    Http::assertSent(function ($request) {
        $body = (string) $request->body();
        $timestamp = $request->header('X-Atreyu-Timestamp')[0];
        $signature = hash_hmac('sha256', implode("\n", [
            $timestamp,
            'POST',
            '/_atreyu/context',
            hash('sha256', $body),
        ]), 'installation-secret');

        return $request->url() === 'https://clara.test/_atreyu/context'
            && $request->hasHeader('X-Atreyu-Signature', $signature);
    });
});
