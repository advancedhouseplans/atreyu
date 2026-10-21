<?php

namespace App\Services;

use App\Models\ReporterInstallation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SourceContextFetcher
{
    /**
     * Fetch per-request Laravel context from a source app.
     *
     * @param  array<int, string>  $debugIds
     * @return array<string, mixed>
     */
    public function fetch(ReporterInstallation|string $source, array $debugIds): array
    {
        if ($debugIds === []) {
            return [];
        }

        [$sourceSlug, $endpoint, $secret] = $this->connection($source);
        if ($endpoint === '' || $secret === '') {
            return [];
        }

        $contexts = [];
        foreach (array_chunk(array_values(array_unique($debugIds)), 25) as $ids) {
            $contexts = [...$contexts, ...$this->fetchChunk($sourceSlug, $endpoint, $secret, $ids)];
        }

        return $contexts;
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<string, mixed>
     */
    private function fetchChunk(string $sourceSlug, string $endpoint, string $secret, array $ids): array
    {
        $path = '/_atreyu/context';
        $url = rtrim($endpoint, '/').$path;
        $body = json_encode(['ids' => $ids], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $message = implode("\n", [$timestamp, 'POST', $path, hash('sha256', $body)]);
        $signature = hash_hmac('sha256', $message, $secret);

        try {
            $response = $this->request($timestamp, $signature)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (Throwable $exception) {
            Log::warning('Atreyu context callback failed', [
                'error' => $exception->getMessage(),
                'source' => $sourceSlug,
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('Atreyu context callback returned an error', [
                'status' => $response->status(),
                'source' => $sourceSlug,
            ]);

            return [];
        }

        $contexts = $response->json('contexts');

        return is_array($contexts) ? $contexts : [];
    }

    private function request(string $timestamp, string $signature): PendingRequest
    {
        return Http::withHeaders([
            'X-Atreyu-Timestamp' => $timestamp,
            'X-Atreyu-Signature' => $signature,
            'Accept' => 'application/json',
        ])->timeout(5);
    }

    /** @return array{string, string, string} */
    private function connection(ReporterInstallation|string $source): array
    {
        if ($source instanceof ReporterInstallation) {
            $source->loadMissing('project');

            return [
                $source->project->slug,
                (string) $source->endpoint,
                $source->secret,
            ];
        }

        $configured = config("bug-sources.{$source}");
        if (! is_array($configured)) {
            return [$source, '', ''];
        }

        return [$source, (string) ($configured['endpoint'] ?? ''), (string) ($configured['secret'] ?? '')];
    }
}
