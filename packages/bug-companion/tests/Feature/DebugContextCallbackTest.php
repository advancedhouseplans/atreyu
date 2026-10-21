<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class DebugContextCallbackTest extends TestCase
{
    public function test_rejects_missing_signature(): void
    {
        $this->postJson('/_atreyu/context', ['ids' => [fake()->uuid()]])->assertUnauthorized();
    }

    public function test_returns_cached_context(): void
    {
        $id = fake()->uuid();
        Cache::put("bug-companion:ctx:{$id}", ['debug_id' => $id, 'request' => ['path' => '/x']], 60);
        $body = json_encode(['ids' => [$id]], JSON_THROW_ON_ERROR);

        $this->call('POST', '/_atreyu/context', [], [], [], $this->signedHeaders($body), $body)
            ->assertOk()
            ->assertJsonPath("contexts.{$id}.debug_id", $id);
    }

    public function test_signature_is_bound_to_method_path_and_body(): void
    {
        $firstId = fake()->uuid();
        $secondId = fake()->uuid();
        $signedBody = json_encode(['ids' => [$firstId]], JSON_THROW_ON_ERROR);
        $changedBody = json_encode(['ids' => [$secondId]], JSON_THROW_ON_ERROR);

        $this->call('POST', '/_atreyu/context', [], [], [], $this->signedHeaders($signedBody), $changedBody)
            ->assertUnauthorized();
    }

    public function test_rejects_stale_timestamp(): void
    {
        $body = json_encode(['ids' => [fake()->uuid()]], JSON_THROW_ON_ERROR);

        $this->call('POST', '/_atreyu/context', [], [], [], $this->signedHeaders($body, time() - 300), $body)
            ->assertUnauthorized();
    }

    /** @return array<string, string> */
    private function signedHeaders(string $body, ?int $timestamp = null): array
    {
        $timestamp ??= time();
        $message = implode("\n", [
            (string) $timestamp,
            'POST',
            '/_atreyu/context',
            hash('sha256', $body),
        ]);

        return [
            'HTTP_X_ATREYU_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_ATREYU_SIGNATURE' => hash_hmac('sha256', $message, 'topsecret'),
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }
}
