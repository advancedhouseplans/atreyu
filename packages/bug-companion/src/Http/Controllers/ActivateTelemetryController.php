<?php

namespace BugCompanion\Http\Controllers;

use BugCompanion\Support\Credential;
use BugCompanion\Support\HeartbeatSender;
use BugCompanion\Support\TokenSigner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivateTelemetryController
{
    public function __invoke(Request $request, HeartbeatSender $heartbeat): Response
    {
        $credential = Credential::fromConfig();
        $token = $request->bearerToken();
        $payload = $credential && is_string($token)
            ? TokenSigner::verify($token, $credential->secret)
            : null;

        if (! $credential || ! $payload || ($payload['key_id'] ?? null) !== $credential->keyId) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        unset($payload['exp']);
        $refreshedToken = TokenSigner::sign(
            $payload,
            $credential->secret,
            (int) config('bug-companion.token_ttl', 900),
        );

        $heartbeat->sendIfDue();

        return response()->json(['token' => $refreshedToken])->cookie(
            'atreyu_active',
            '1',
            max(1, (int) config('bug-companion.activation_ttl', 2)),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'Lax',
        );
    }
}
