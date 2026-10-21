<?php

namespace App\Support;

use Illuminate\Support\Str;

class ReportFingerprint
{
    /** @param array<string, mixed> $metadata */
    public static function make(string $source, string $environment, string $type, string $title, array $metadata): ?string
    {
        if ($type !== 'bug') {
            return null;
        }

        $url = parse_url((string) ($metadata['url'] ?? ''), PHP_URL_PATH) ?: '/';

        return hash('sha256', implode('|', [
            Str::lower($source),
            Str::lower($environment),
            self::normalize($title),
            self::normalizePath($url),
        ]));
    }

    private static function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[0-9a-f]{8}-[0-9a-f-]{27,}/i', ':uuid')
            ->replaceMatches('/\b\d+\b/', ':id')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private static function normalizePath(string $path): string
    {
        return Str::of($path)
            ->replaceMatches('#/[0-9a-f]{8}-[0-9a-f-]{27,}(?=/|$)#i', '/:uuid')
            ->replaceMatches('#/\d+(?=/|$)#', '/:id')
            ->lower()
            ->toString();
    }
}
