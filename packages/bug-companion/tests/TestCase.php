<?php

namespace BugCompanion\Tests;

use BugCompanion\BugCompanionServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BugCompanionServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bug-companion.secret', 'topsecret');
        $app['config']->set('bug-companion.source_slug', 'ahp');
        $app['config']->set('bug-companion.endpoint', 'https://bugs.test');
    }
}
