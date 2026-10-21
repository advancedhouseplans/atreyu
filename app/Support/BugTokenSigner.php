<?php

namespace App\Support;

use App\Models\ReporterInstallation;

class BugTokenSigner
{
    /**
     * @return array{source_slug:string, reporter_installation_id:int|null, env:string, endpoint:string, source_url:string|null, user_id:mixed, user_email:string|null, user_name:string|null, exp:int}|null
     */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$body, $signature] = $parts;

        $json = self::b64UrlDecode($body);
        $payload = $json === false ? null : json_decode($json, true);

        if (! self::hasValidShape($payload)) {
            return null;
        }

        $installation = self::installation($payload);

        if (($payload['key_id'] ?? null) !== null && ! $installation) {
            return null;
        }

        if ($installation) {
            $sourceSlug = $installation->project->slug;
            $secret = $installation->secret;
        } else {
            $sourceSlug = $payload['source_slug'] ?? null;
            $secret = config("bug-sources.{$sourceSlug}.secret");
        }

        if (! is_string($sourceSlug) || ! is_string($secret) || $secret === '') {
            return null;
        }

        $expected = self::b64UrlEncode(hash_hmac('sha256', $body, $secret, true));

        if (! hash_equals($expected, $signature) || time() > $payload['exp']) {
            return null;
        }

        return [
            'source_slug' => $sourceSlug,
            'reporter_installation_id' => $installation?->id,
            'env' => $payload['env'],
            'endpoint' => $payload['endpoint'],
            'source_url' => is_string($payload['source_url'] ?? null) ? $payload['source_url'] : null,
            'user_id' => $payload['user_id'],
            'user_email' => $payload['user_email'],
            'user_name' => $payload['user_name'],
            'exp' => $payload['exp'],
        ];
    }

    /** @param array<string, mixed>|null $payload */
    private static function hasValidShape(?array $payload): bool
    {
        return is_array($payload)
            && (is_string($payload['key_id'] ?? null) || is_string($payload['source_slug'] ?? null))
            && is_string($payload['env'] ?? null)
            && is_string($payload['endpoint'] ?? null)
            && array_key_exists('user_id', $payload)
            && array_key_exists('user_email', $payload)
            && (is_string($payload['user_email']) || $payload['user_email'] === null)
            && array_key_exists('user_name', $payload)
            && (is_string($payload['user_name']) || $payload['user_name'] === null)
            && is_int($payload['exp'] ?? null);
    }

    /** @param array<string, mixed> $payload */
    private static function installation(array $payload): ?ReporterInstallation
    {
        $keyId = $payload['key_id'] ?? null;

        if (! is_string($keyId) || ! str_starts_with($keyId, 'atry_')) {
            return null;
        }

        return ReporterInstallation::query()
            ->with('project')
            ->where('key_id', $keyId)
            ->whereNull('revoked_at')
            ->first();
    }

    private static function b64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function b64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
