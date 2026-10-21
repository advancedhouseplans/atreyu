<?php

namespace BugCompanion\Support;

use Throwable;

class ContextBuffer
{
    /** @var array<int, array<string, mixed>> */
    private array $queries = [];

    /** @var array<string, array<string, mixed>> */
    private array $models = [];

    /** @var array<int, array<string, mixed>> */
    private array $logs = [];

    /** @var array<int, array<string, mixed>> */
    private array $exceptions = [];

    /** @var array<string, mixed> */
    private array $request = [];

    /** @var array<string, mixed>|null */
    private ?array $user = null;

    /** @var array<string, mixed> */
    private array $response = [];

    private readonly float $startedAt;

    public function __construct(
        private readonly string $debugId,
        string $method,
        string $path,
        private readonly Redactor $redactor = new Redactor,
    ) {
        $this->startedAt = microtime(true);
        $this->request = ['method' => $method, 'path' => $path];
    }

    public function debugId(): string
    {
        return $this->debugId;
    }

    /** @param array<string, mixed> $context */
    public function setRequestContext(array $context): void
    {
        $this->request = [...$this->request, ...$this->redactor->value($context)];
    }

    public function setUser(?string $guard, object $user): void
    {
        if (! config('bug-companion.telemetry.user', true)) {
            return;
        }

        $this->user = $this->redactor->value([
            'guard' => $guard,
            'model' => $user::class,
            'id' => method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : null,
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
        ]);
    }

    public function addQuery(string $sql, array $bindings, float $timeMs, string $connection): void
    {
        if (! config('bug-companion.telemetry.queries', true) || count($this->queries) >= $this->cap('queries')) {
            return;
        }

        $this->queries[] = [
            'sql' => $this->redactor->value($sql),
            'bindings' => array_map($this->redactor->queryBinding(...), $bindings),
            'time_ms' => round($timeMs, 2),
            'connection' => $connection,
        ];
    }

    /** @param array<int, string> $changed */
    public function addModel(string $operation, string $class, mixed $key, array $changed = []): void
    {
        if (! config('bug-companion.telemetry.models', true)) {
            return;
        }

        $dedupeKey = $operation.'|'.$class.'|'.serialize($key);
        if (isset($this->models[$dedupeKey]) || count($this->models) >= $this->cap('models')) {
            return;
        }

        $this->models[$dedupeKey] = [
            'operation' => $operation,
            'class' => $class,
            'id' => $this->redactor->value($key),
            'changed' => array_values($changed),
        ];
    }

    /** @param array<string, mixed> $context */
    public function addLog(string $level, string $message, array $context = []): void
    {
        if (! config('bug-companion.telemetry.logs', true)
            || ! in_array(strtolower($level), config('bug-companion.telemetry.log_levels', []), true)
            || count($this->logs) >= $this->cap('logs')) {
            return;
        }

        $this->logs[] = [
            'level' => strtolower($level),
            'message' => $this->redactor->value($message),
            'context' => $this->redactor->value($context),
        ];
    }

    public function addException(Throwable $exception): void
    {
        if (! config('bug-companion.telemetry.exceptions', true) || count($this->exceptions) >= $this->cap('exceptions')) {
            return;
        }

        $frames = collect($exception->getTrace())
            ->filter(fn (array $frame): bool => isset($frame['file']))
            ->take(30)
            ->map(fn (array $frame): array => [
                'file' => $this->relativePath((string) $frame['file']),
                'line' => $frame['line'] ?? null,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
            ])->values()->all();

        $this->exceptions[] = [
            'class' => $exception::class,
            'message' => $this->redactor->value($exception->getMessage()),
            'file' => $this->relativePath($exception->getFile()),
            'line' => $exception->getLine(),
            'frames' => $frames,
        ];
    }

    public function finish(int $status): void
    {
        $this->response = [
            'status' => $status,
            'duration_ms' => round((microtime(true) - $this->startedAt) * 1000, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => 2,
            'debug_id' => $this->debugId,
            'captured_at' => now()->toIso8601String(),
            'application' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
                'version' => config('bug-companion.version'),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
            'request' => $this->request,
            'user' => $this->user,
            'response' => $this->response,
            'models' => array_values($this->models),
            'queries' => $this->queries,
            'logs' => $this->logs,
            'exceptions' => $this->exceptions,
        ];
    }

    private function cap(string $type): int
    {
        return max(0, (int) config("bug-companion.telemetry.max_{$type}", 100));
    }

    private function relativePath(string $path): string
    {
        $basePath = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $basePath) ? substr($path, strlen($basePath)) : $path;
    }
}
