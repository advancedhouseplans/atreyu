<?php

namespace App\Http\Middleware;

use App\Models\ReporterInstallation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigureReporterCors
{
    /**
     * Add heartbeat-discovered reporter origins for only the report endpoint.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        if (! $request->is('api/reports') || ! is_string($origin) || $origin === '') {
            return $next($request);
        }

        $configuredOrigins = config('cors.allowed_origins', []);
        if (! is_array($configuredOrigins) || in_array($origin, $configuredOrigins, true)) {
            return $next($request);
        }

        $registered = ReporterInstallation::query()
            ->whereNull('revoked_at')
            ->get(['allowed_origins'])
            ->contains(fn (ReporterInstallation $installation): bool => in_array(
                $origin,
                $installation->allowed_origins ?? [],
                true,
            ));

        if (! $registered) {
            return $next($request);
        }

        config()->set('cors.allowed_origins', [...$configuredOrigins, $origin]);

        try {
            return $next($request);
        } finally {
            config()->set('cors.allowed_origins', $configuredOrigins);
        }
    }
}
