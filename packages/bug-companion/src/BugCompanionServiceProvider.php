<?php

namespace BugCompanion;

use Illuminate\Support\ServiceProvider;

class BugCompanionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bug-companion.php', 'bug-companion');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/bug-companion.php' => config_path('bug-companion.php'),
        ], 'bug-companion-config');
    }
}
