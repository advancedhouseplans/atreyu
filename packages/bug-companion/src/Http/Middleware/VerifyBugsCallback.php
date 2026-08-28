<?php

namespace BugCompanion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBugsCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $ts = (string) $request->header('X-Bugs-Timestamp', '');
        $sig = (string) $request->header('X-Bugs-Signature', '');
        $secret = (string) config('bug-companion.secret', '');

        if ($ts === '' || $sig === '' || $secret === '') {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        if (abs(time() - (int) $ts) > 60) {
            return response()->json(['error' => 'stale'], 401);
        }

        $expected = hash_hmac('sha256', "{$ts}.".$request->getContent(), $secret);
        if (! hash_equals($expected, $sig)) {
            return response()->json(['error' => 'bad signature'], 401);
        }

        return $next($request);
    }
}
