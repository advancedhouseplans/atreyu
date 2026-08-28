<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\BugCompanionServiceProvider;
use BugCompanion\Tests\TestCase;
use Illuminate\Support\ServiceProvider;

class ErrorViewsPublishTest extends TestCase
{
    public function test_errors_are_publishable(): void
    {
        $publishes = ServiceProvider::pathsToPublish(
            BugCompanionServiceProvider::class,
            'bug-companion-errors',
        );
        $this->assertNotEmpty($publishes);
    }
}
