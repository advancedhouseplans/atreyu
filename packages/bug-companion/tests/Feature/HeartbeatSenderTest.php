<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\HeartbeatSender;
use BugCompanion\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HeartbeatSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        config()->set('bug-companion.key', 'atry_test.installation-secret');
        config()->set('bug-companion.url', 'https://atreyu.test');
        config()->set('app.url', 'https://clara.test');
        config()->set('app.name', 'Clara');
    }

    public function test_it_sends_a_signed_heartbeat_once_per_hour(): void
    {
        Http::fake(['*' => Http::response(['project' => 'clara'])]);

        app(HeartbeatSender::class)->sendIfDue();
        app(HeartbeatSender::class)->sendIfDue();

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $body = (string) $request->body();
            $timestamp = $request->header('X-Atreyu-Timestamp')[0];
            $expected = hash_hmac('sha256', implode("\n", [
                $timestamp,
                'POST',
                '/api/reporter/heartbeat',
                hash('sha256', $body),
            ]), 'installation-secret');

            return $request->url() === 'https://atreyu.test/api/reporter/heartbeat'
                && $request->hasHeader('X-Atreyu-Key', 'atry_test')
                && $request->hasHeader('X-Atreyu-Signature', $expected)
                && $request->data()['endpoint'] === 'https://clara.test'
                && $request->data()['app_name'] === 'Clara';
        });
    }

    public function test_it_infers_the_endpoint_from_the_current_request_when_app_url_is_blank(): void
    {
        Http::fake(['*' => Http::response(['project' => 'clara'])]);
        config()->set('app.url', '');
        app()->instance('request', Request::create('https://ahp.test/plans/123'));

        app(HeartbeatSender::class)->sendIfDue();

        Http::assertSent(fn ($request): bool => $request->data()['endpoint'] === 'https://ahp.test');
    }
}
