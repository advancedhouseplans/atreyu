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
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('bug-companion.key', 'topsecret');
        $app['config']->set('bug-companion.project', 'ahp');
        $app['config']->set('bug-companion.url', 'https://bugs.test');
    }
}
