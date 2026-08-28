<?php

namespace BugCompanion\Support;

use Throwable;

class ContextBuffer
{
    private array $queries = [];

    private array $models = [];

    private array $exceptions = [];

    private ?string $routeName = null;

    private ?string $authGuard = null;

    private mixed $authUserId = null;

    private ?string $authEmail = null;

    public function __construct(
        private readonly string $debugId,
        private readonly string $method,
        private readonly string $path,
    ) {}

    public function setRouteName(?string $name): void
    {
        $this->routeName = $name;
    }

    public function setAuth(?string $guard, mixed $userId, ?string $email): void
    {
        $this->authGuard = $guard;
        $this->authUserId = $userId;
        $this->authEmail = $email;
    }

    public function addQuery(string $sql, array $bindings, float $timeMs, string $connection): void
    {
        $cap = (int) config('bug-companion.max_queries', 100);
        if (count($this->queries) >= $cap) {
            return;
        }
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => array_map(fn ($v) => is_string($v) ? substr($v, 0, 200) : $v, $bindings),
            'time_ms' => $timeMs,
            'connection' => $connection,
        ];
    }

    public function addModel(string $class, mixed $key): void
    {
        $cap = (int) config('bug-companion.max_models', 200);
        if (count($this->models) >= $cap) {
            return;
        }
        $this->models[] = ['class' => $class, 'key' => $key];
    }

    public function addException(Throwable $e): void
    {
        $frames = [];
        foreach (array_slice($e->getTrace(), 0, 30) as $f) {
            $frames[] = ($f['file'] ?? '?').':'.($f['line'] ?? '?');
        }
        $this->exceptions[] = [
            'class' => $e::class,
            'message' => $e->getMessage(),
            'frames' => $frames,
        ];
    }

    public function toArray(): array
    {
        return [
            'debug_id' => $this->debugId,
            'request' => [
                'method' => $this->method,
                'path' => $this->path,
                'route_name' => $this->routeName,
                'timestamp' => now()->toIso8601String(),
            ],
            'auth' => [
                'guard' => $this->authGuard,
                'user_id' => $this->authUserId,
                'user_email' => $this->authEmail,
            ],
            'models' => $this->models,
            'queries' => $this->queries,
            'exceptions' => $this->exceptions,
        ];
    }
}
