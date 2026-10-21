<?php

namespace App\Http\Middleware;

use App\Models\ReporterInstallation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyReporterSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $keyId = (string) $request->header('X-Atreyu-Key', '');
        $timestamp = (string) $request->header('X-Atreyu-Timestamp', '');
        $signature = (string) $request->header('X-Atreyu-Signature', '');
        $installation = ReporterInstallation::query()
            ->where('key_id', $keyId)
            ->whereNull('revoked_at')
            ->first();

        if (! $installation || ! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 60) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $message = implode("\n", [
            $timestamp,
            strtoupper($request->method()),
            '/'.$request->path(),
            hash('sha256', $request->getContent()),
        ]);

        if (! hash_equals(hash_hmac('sha256', $message, $installation->secret), $signature)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('reporter.installation', $installation);

        return $next($request);
    }
}
