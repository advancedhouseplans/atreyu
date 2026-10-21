<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;

class BugWidgetDirectiveTest extends TestCase
{
    public function test_directive_emits_script_with_valid_token(): void
    {
        $rendered = view('bug-companion::widget')->render();
        preg_match('/data-atreyu-config="([^"]+)"/', $rendered, $m);
        $this->assertNotEmpty($m[1] ?? null);
        $payload = TokenSigner::verify($m[1], 'topsecret');
        $this->assertSame('ahp', $payload['source_slug']);
        $this->assertSame('https://bugs.test', $payload['endpoint']);
        $this->assertSame('http://localhost', $payload['source_url']);
        $this->assertTrue($payload['launcher']);
        $this->assertSame(300000, $payload['diagnostic_window_ms']);
        $this->assertSame(5, $payload['diagnostic_requests']);
        $this->assertStringContainsString('https://bugs.test/widget.js', $rendered);
    }

    public function test_directive_is_noop_when_disabled(): void
    {
        config()->set('bug-companion.enabled', false);
        $rendered = \Blade::render('@atreyu');

        $this->assertSame('', trim($rendered));
    }

    public function test_trigger_directive_renders_an_unstyled_data_attribute(): void
    {
        $rendered = \Blade::render('<button @atreyuTrigger class="my-app-button">Report a problem</button>');

        $this->assertStringContainsString('data-atreyu-open', $rendered);
        $this->assertStringContainsString('class="my-app-button"', $rendered);
    }
}
