<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\TokenSigner;
use BugCompanion\Tests\TestCase;

class TokenSignerTest extends TestCase
{
    public function test_roundtrip(): void
    {
        $t = TokenSigner::sign(['source_slug' => 'ahp', 'user_id' => 1], 'topsecret', 60);
        $this->assertNotNull(TokenSigner::verify($t, 'topsecret'));
        $this->assertNull(TokenSigner::verify($t.'x', 'topsecret'));
    }

    public function test_expired_returns_null(): void
    {
        $t = TokenSigner::sign(['source_slug' => 'ahp'], 'topsecret', -10);
        $this->assertNull(TokenSigner::verify($t, 'topsecret'));
    }
}
