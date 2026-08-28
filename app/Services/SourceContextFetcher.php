<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SourceContextFetcher
{
    /**
     * Fetch per-request Laravel context from a source app.
     *
     * @param  array<int, string>  $debugIds
     * @return array<string, array|null>
     */
    public function fetch(string $sourceSlug, array $debugIds): array
    {
        if (empty($debugIds)) {
            return [];
        }

        $source = config("bug-sources.{$sourceSlug}");
        if (! is_array($source) || empty($source['endpoint']) || empty($source['secret'])) {
            return [];
        }

        $ids = implode('|', $debugIds);
        $url = rtrim((string) $source['endpoint'], '/')."/_bug-debug/{$ids}";
        $body = '';
        $ts = (string) time();
        $sig = hash_hmac('sha256', "{$ts}.{$body}", (string) $source['secret']);

        try {
            $resp = Http::withHeaders([
                'X-Bugs-Timestamp' => $ts,
                'X-Bugs-Signature' => $sig,
                'Accept' => 'application/json',
            ])->timeout(5)->get($url);
        } catch (Throwable $e) {
            Log::warning('bug-companion callback failed', ['error' => $e->getMessage(), 'source' => $sourceSlug]);

            return [];
        }

        if (! $resp->successful()) {
            Log::warning('bug-companion callback non-2xx', ['status' => $resp->status(), 'source' => $sourceSlug]);

            return [];
        }

        $json = $resp->json();

        return is_array($json) ? $json : [];
    }
}
