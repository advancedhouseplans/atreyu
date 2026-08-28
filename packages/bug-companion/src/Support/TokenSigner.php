<?php

namespace BugCompanion\Support;

class TokenSigner
{
    public static function sign(array $payload, string $secret, int $ttl): string
    {
        $payload['exp'] = time() + $ttl;
        $body = self::b64UrlEncode(json_encode($payload));
        $sig = self::b64UrlEncode(hash_hmac('sha256', $body, $secret, true));

        return "{$body}.{$sig}";
    }

    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$body, $sig] = $parts;
        $expected = self::b64UrlEncode(hash_hmac('sha256', $body, $secret, true));
        if (! hash_equals($expected, $sig)) {
            return null;
        }
        $json = self::b64UrlDecode($body);
        $payload = $json === false ? null : json_decode($json, true);
        if (! is_array($payload) || (isset($payload['exp']) && time() > (int) $payload['exp'])) {
            return null;
        }

        return $payload;
    }

    private static function b64UrlEncode(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function b64UrlDecode(string $s): string|false
    {
        $pad = strlen($s) % 4;
        if ($pad) {
            $s .= str_repeat('=', 4 - $pad);
        }

        return base64_decode(strtr($s, '-_', '+/'), true);
    }
}
