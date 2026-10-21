<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\Redactor;
use BugCompanion\Tests\TestCase;

class RedactorTest extends TestCase
{
    public function test_redacts_sensitive_keys_and_recognizable_credentials(): void
    {
        $redactor = app(Redactor::class);

        $value = $redactor->value([
            'email' => 'reporter@example.com',
            'password' => 'correct horse battery staple',
            'nested' => ['access_token' => 'secret-token'],
        ]);

        $this->assertSame('reporter@example.com', $value['email']);
        $this->assertSame('[REDACTED]', $value['password']);
        $this->assertSame('[REDACTED]', $value['nested']['access_token']);
        $this->assertSame('[REDACTED]', $redactor->queryBinding('Bearer a-secret-token'));
    }
}
