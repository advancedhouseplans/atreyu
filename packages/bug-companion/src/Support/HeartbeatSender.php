<?php

namespace BugCompanion\Support;

use BugCompanion\BugCompanionServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HeartbeatSender
{
    public function sendIfDue(): void
    {
        $credential = Credential::fromConfig();
        $atreyuUrl = rtrim((string) config('bug-companion.url'), '/');
        if (! $credential || $atreyuUrl === '') {
            return;
        }

        $cacheKey = 'atreyu:heartbeat:'.$credential->keyId;
        if (! Cache::add($cacheKey, true, 3600)) {
            return;
        }

        $endpoint = $this->endpoint();
        if ($endpoint === '') {
            Cache::forget($cacheKey);

            return;
        }

        $body = json_encode([
            'endpoint' => $endpoint,
            'app_name' => (string) config('app.name'),
            'environment' => app()->environment(),
            'package_version' => BugCompanionServiceProvider::VERSION,
            'app_version' => config('bug-companion.version'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $path = '/api/reporter/heartbeat';
        $message = implode("\n", [$timestamp, 'POST', $path, hash('sha256', $body)]);

        try {
            $response = Http::withHeaders([
                'X-Atreyu-Key' => $credential->keyId,
                'X-Atreyu-Timestamp' => $timestamp,
                'X-Atreyu-Signature' => hash_hmac('sha256', $message, $credential->secret),
                'Accept' => 'application/json',
            ])->withBody($body, 'application/json')->timeout(3)->post($atreyuUrl.$path);

            if (! $response->successful()) {
                Cache::put($cacheKey, true, 300);
                Log::warning('Atreyu heartbeat was rejected', [
                    'key_id' => $credential->keyId,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            Cache::put($cacheKey, true, 300);
            Log::warning('Atreyu heartbeat failed', [
                'key_id' => $credential->keyId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function endpoint(): string
    {
        $configured = rtrim((string) config('app.url'), '/');
        if ($configured !== '') {
            return $configured;
        }

        if (! app()->bound('request')) {
            return '';
        }

        $request = app('request');

        return $request instanceof Request ? rtrim($request->root(), '/') : '';
    }
}
