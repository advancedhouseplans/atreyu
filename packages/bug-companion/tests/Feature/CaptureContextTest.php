<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\ContextBuffer;
use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class CaptureContextTest extends TestCase
{
    public function test_middleware_stashes_context_and_sets_header(): void
    {
        Route::middleware('web')->get('/test-capture/{plan}', function (string $plan) {
            $b = app(ContextBuffer::class);
            $b->addModel('retrieved', 'App\\Models\\Plan', 1);
            DB::select('select ? as plan_id', [(int) $plan]);
            Log::warning('Plan lookup was slow', ['plan_id' => $plan, 'password' => 'hidden']);

            return 'ok';
        })->name('plans.show');

        $resp = $this->withCookie('atreyu_active', '1')->get('/test-capture/42?preview=yes');
        $resp->assertOk();
        $debugId = $resp->headers->get('X-Atreyu-Debug-Id');
        $this->assertNotEmpty($debugId);

        $ctx = Cache::get("bug-companion:ctx:{$debugId}");
        $this->assertIsArray($ctx);
        $this->assertSame('App\\Models\\Plan', $ctx['models'][0]['class']);
        $this->assertSame('retrieved', $ctx['models'][0]['operation']);
        $this->assertSame(200, $ctx['response']['status']);
        $this->assertSame('plans.show', $ctx['request']['route_name']);
        $this->assertSame('42', $ctx['request']['route_parameters']['plan']);
        $this->assertSame(['preview'], $ctx['request']['query_fields']);
        $this->assertSame(42, $ctx['queries'][0]['bindings'][0]);
        $this->assertSame('[REDACTED]', $ctx['logs'][0]['context']['password']);
    }

    public function test_middleware_does_no_capture_work_without_an_active_component(): void
    {
        Route::middleware('web')->get('/test-inactive', fn () => [
            'buffer_bound' => app()->bound(ContextBuffer::class),
        ]);

        $response = $this->get('/test-inactive');

        $response->assertOk()
            ->assertJson(['buffer_bound' => false]);
        $this->assertNull($response->headers->get('X-Atreyu-Debug-Id'));
    }

    public function test_signed_active_header_enables_capture_for_api_requests(): void
    {
        Route::middleware('api')->get('/api/test-capture', fn () => 'ok');
        $token = TokenSigner::sign(['key_id' => 'ahp'], 'topsecret', 60);

        $response = $this->withHeader('X-Atreyu-Active', $token)
            ->get('/api/test-capture');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('X-Atreyu-Debug-Id'));
    }
}
