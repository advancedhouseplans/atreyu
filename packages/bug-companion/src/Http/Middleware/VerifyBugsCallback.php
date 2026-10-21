<?php

namespace BugCompanion\Http\Middleware;

use BugCompanion\Support\Credential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBugsCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = (string) $request->header('X-Atreyu-Timestamp', '');
        $signature = (string) $request->header('X-Atreyu-Signature', '');
        $credential = Credential::fromConfig();

        if (! ctype_digit($timestamp) || $signature === '' || ! $credential) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        if (abs(time() - (int) $timestamp) > 60) {
            return response()->json(['error' => 'stale'], 401);
        }

        $message = implode("\n", [
            $timestamp,
            strtoupper($request->method()),
            '/'.$request->path(),
            hash('sha256', $request->getContent()),
        ]);
        $expected = hash_hmac('sha256', $message, $credential->secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['error' => 'bad signature'], 401);
        }

        return $next($request);
    }
}
