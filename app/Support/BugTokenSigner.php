<?php

namespace App\Support;

class BugTokenSigner
{
    /**
     * Verify a widget config token. Returns the decoded payload array on success, null on any failure.
     *
     * @return array{source_slug:string, env:string, endpoint:string, user_id:mixed, user_email:string, user_name:string, exp:int}|null
     */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$b64Payload, $b64Sig] = $parts;

        $payloadJson = self::b64UrlDecode($b64Payload);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload) || ! isset($payload['source_slug'], $payload['exp'])) {
            return null;
        }

        $source = config("bug-sources.{$payload['source_slug']}");
        if (! is_array($source) || empty($source['secret'])) {
            return null;
        }

        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $b64Payload, $source['secret'], true)), '+/', '-_'), '=');
        if (! hash_equals($expected, $b64Sig)) {
            return null;
        }

        if (time() > (int) $payload['exp']) {
            return null;
        }

        return $payload;
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
