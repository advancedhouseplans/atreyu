<?php

namespace BugCompanion;

use BugCompanion\Http\Middleware\CaptureContext;
use BugCompanion\Http\Middleware\VerifyBugsCallback;
use BugCompanion\Support\ContextBuffer;
use BugCompanion\Support\Redactor;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

class BugCompanionServiceProvider extends ServiceProvider
{
    public const string VERSION = '0.2.0';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bug-companion.php', 'bug-companion');
        $this->app->singleton(Redactor::class);
    }

    public function boot(Router $router): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bug-companion');

        $this->publishes([
            __DIR__.'/../config/bug-companion.php' => config_path('bug-companion.php'),
        ], 'bug-companion-config');

        $this->publishes([
            __DIR__.'/../resources/views/errors' => resource_path('views/errors'),
        ], 'bug-companion-errors');

        $router->aliasMiddleware('bugs.callback', VerifyBugsCallback::class);
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::directive('atreyu', function () {
            return "<?php if (config('bug-companion.enabled', true)) { echo view('bug-companion::widget')->render(); } ?>";
        });
        Blade::directive('atreyuTrigger', fn () => 'data-atreyu-open');
        Blade::directive('bugWidget', fn () => "<?php if (config('bug-companion.enabled', true)) { echo view('bug-companion::widget')->render(); } ?>");

        if (! config('bug-companion.enabled', true)
            || blank(config('bug-companion.url'))
            || blank(config('bug-companion.key'))) {
            return;
        }

        $router->pushMiddlewareToGroup('web', CaptureContext::class);
        $router->pushMiddlewareToGroup('api', CaptureContext::class);
        $this->app->afterResolving(HttpKernel::class, function ($kernel): void {
            $kernel->appendMiddlewareToGroup('web', CaptureContext::class);
            $kernel->appendMiddlewareToGroup('api', CaptureContext::class);
        });

        $this->captureModelEvents();

        DB::listen(function ($query): void {
            if ($this->app->bound(ContextBuffer::class)) {
                $this->app->make(ContextBuffer::class)
                    ->addQuery($query->sql, $query->bindings ?? [], $query->time ?? 0.0, $query->connectionName);
            }
        });

        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            if ($this->app->bound(ContextBuffer::class)) {
                $this->app->make(ContextBuffer::class)
                    ->addLog($event->level, $event->message, $event->context);
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

    private function captureModelEvents(): void
    {
        Model::retrieved(fn (Model $model) => $this->captureModel('retrieved', $model));
        Model::created(fn (Model $model) => $this->captureModel('created', $model, array_keys($model->getChanges())));
        Model::updated(fn (Model $model) => $this->captureModel('updated', $model, array_keys($model->getChanges())));
        Model::deleted(fn (Model $model) => $this->captureModel('deleted', $model));
    }

    /** @param array<int, string> $changed */
    private function captureModel(string $operation, Model $model, array $changed = []): void
    {
        if ($this->app->bound(ContextBuffer::class)) {
            $this->app->make(ContextBuffer::class)
                ->addModel($operation, $model::class, $model->getKey(), $changed);
        }
    }
}
