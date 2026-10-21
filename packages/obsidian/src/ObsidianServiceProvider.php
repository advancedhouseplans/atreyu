<?php

namespace Obsidian;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ObsidianServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'ob');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'obsidian');

        Route::middleware('web')->get('/_obsidian', function () {
            abort_unless(app()->isLocal(), 404);

            return view('obsidian::showcase');
        })->name('obsidian.showcase');
    }
}
