<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ActivateTelemetryTest extends TestCase
{
    public function test_a_signed_widget_activates_capture_and_sends_the_heartbeat(): void
    {
        Http::fake(['*' => Http::response(['project' => 'ahp'])]);
        $token = TokenSigner::sign([
            'key_id' => 'ahp',
            'env' => 'testing',
            'endpoint' => 'https://bugs.test',
        ], 'topsecret', 60);

        $this->withToken($token)
            ->get('/_atreyu/activate')
            ->assertOk()
            ->assertJsonStructure(['token'])
            ->assertCookie('atreyu_active');

        Http::assertSentCount(1);
    }

    public function test_activation_rejects_an_invalid_widget_token(): void
    {
        $this->withToken('invalid.token')
            ->get('/_atreyu/activate')
            ->assertUnauthorized()
            ->assertCookieMissing('atreyu_active');
    }

    public function test_component_deactivation_expires_the_capture_cookie(): void
    {
        $this->withCookie('atreyu_active', '1')
            ->get('/_atreyu/deactivate')
            ->assertNoContent()
            ->assertCookieExpired('atreyu_active');
    }
}
