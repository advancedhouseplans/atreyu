<?php

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
        return Http::response(['id1' => ['route_name' => 'x'], 'id2' => null], 200);
    });

    $out = app(SourceContextFetcher::class)->fetch('ahp', ['id1', 'id2']);
    expect($out)->toBe(['id1' => ['route_name' => 'x'], 'id2' => null]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Bugs-Signature')
            && $request->hasHeader('X-Bugs-Timestamp')
            && str_ends_with((string) $request->url(), '/_bug-debug/id1%7Cid2');
    });
});

it('returns [] on 5xx', function () {
    Http::fake(['*' => Http::response('', 500)]);
    expect(app(SourceContextFetcher::class)->fetch('ahp', ['a']))->toBe([]);
});

it('returns [] on unknown source', function () {
    expect(app(SourceContextFetcher::class)->fetch('ghost', ['a']))->toBe([]);
});
