<?php

namespace BugCompanion;

use BugCompanion\Http\Middleware\CaptureContext;
use BugCompanion\Http\Middleware\VerifyBugsCallback;
use BugCompanion\Support\ContextBuffer;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Throwable;

class BugCompanionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bug-companion.php', 'bug-companion');
    }

    public function boot(Router $router): void
    {
        $this->publishes([
            __DIR__.'/../config/bug-companion.php' => config_path('bug-companion.php'),
        ], 'bug-companion-config');

        $router->aliasMiddleware('bugs.callback', VerifyBugsCallback::class);
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if (! config('bug-companion.enabled', true)) {
            return;
        }

        $this->app->afterResolving(HttpKernel::class, function ($kernel): void {
            $kernel->appendMiddlewareToGroup('web', CaptureContext::class);
        });

        Model::retrieved(function ($model): void {
            if ($this->app->bound(ContextBuffer::class)) {
                $this->app->make(ContextBuffer::class)
                    ->addModel($model::class, $model->getKey());
            }
        });

        DB::listen(function ($query): void {
            if ($this->app->bound(ContextBuffer::class)) {
                $this->app->make(ContextBuffer::class)
                    ->addQuery($query->sql, $query->bindings ?? [], $query->time ?? 0.0, $query->connectionName);
            }
        });

        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);
        if (method_exists($handler, 'reportable')) {
            $handler->reportable(function (Throwable $e): void {
                if ($this->app->bound(ContextBuffer::class)) {
                    $this->app->make(ContextBuffer::class)->addException($e);
                }
            });
        }
    }
}
