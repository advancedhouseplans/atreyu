<?php

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
