<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporterHeartbeatRequest;
use App\Models\ReporterInstallation;
use Illuminate\Http\JsonResponse;

class ReporterHeartbeatController extends Controller
{
    public function __invoke(ReporterHeartbeatRequest $request): JsonResponse
    {
        /** @var ReporterInstallation $installation */
        $installation = $request->attributes->get('reporter.installation');
        $validated = $request->validated();
        $origin = $this->origin((string) $validated['endpoint']);

        $installation->update([
            'endpoint' => rtrim((string) $validated['endpoint'], '/'),
            'environment' => $validated['environment'],
            'allowed_origins' => $origin ? [$origin] : [],
            'package_version' => $validated['package_version'],
            'app_version' => $validated['app_version'] ?? null,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'project' => $installation->project->slug,
            'installation' => $installation->key_id,
            'context_ttl' => 3600,
        ]);
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
