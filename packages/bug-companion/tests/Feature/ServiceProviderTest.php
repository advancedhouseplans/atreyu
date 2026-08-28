<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_merges(): void
    {
        $this->assertSame('ahp', config('bug-companion.source_slug'));
    }
}
