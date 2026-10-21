<?php

namespace App\Support;

class ReporterCredential
{
    public static function generateSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function format(string $keyId, string $secret): string
    {
        return $keyId.'.'.$secret;
    }
}
