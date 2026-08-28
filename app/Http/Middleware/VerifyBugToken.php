<?php

namespace App\Http\Middleware;

use App\Support\BugTokenSigner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBugToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'missing token'], 401);
        }

        $token = substr($header, 7);
        $payload = BugTokenSigner::verify($token);
        if ($payload === null) {
            return response()->json(['error' => 'invalid token'], 401);
        }

        $request->attributes->set('bug.token', $payload);

        return $next($request);
    }
}
