<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\ContextBuffer;
use BugCompanion\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class CaptureContextTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

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
