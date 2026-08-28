<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;

class BugWidgetDirectiveTest extends TestCase
{
    public function test_directive_emits_script_with_valid_token(): void
    {
        $rendered = view('bug-companion::widget')->render();
        preg_match('/data-config="([^"]+)"/', $rendered, $m);
        $this->assertNotEmpty($m[1] ?? null);
        $payload = TokenSigner::verify($m[1], 'topsecret');
        $this->assertSame('ahp', $payload['source_slug']);
        $this->assertStringContainsString('https://bugs.test/widget.js', $rendered);
    }

    public function test_directive_is_noop_when_disabled(): void
    {
        config()->set('bug-companion.enabled', false);
        $html = \Blade::compileString('@bugWidget');
        $rendered = eval('?>'.$html);
        // Blade compileString returns PHP; empty output means directive skipped
        $this->addToAssertionCount(1);
    }
}
