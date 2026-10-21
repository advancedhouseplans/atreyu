<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReporterInstallation;
use App\Models\User;
use App\Notifications\ReportReceived;
use App\Services\SourceContextFetcher;
use App\Support\ReportFingerprint;
use App\Support\UserAgentParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ReportsController extends Controller
{
    public function __construct(private readonly SourceContextFetcher $fetcher) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $token = $request->attributes->get('bug.token');
        $reporterKey = $token['user_email'] ?? $token['user_id'] ?? $request->ip();
        $key = "reports:{$token['source_slug']}:{$reporterKey}";
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'rate limited'], 429);
        }
        RateLimiter::hit($key, 60);

        $meta = json_decode((string) $request->input('metadata'), true) ?: [];

        $ua = UserAgentParser::parse((string) ($meta['user_agent'] ?? ''));
        $sourceSlug = $token['source_slug'];
        $environment = $token['env'] ?? 'production';
        $fingerprint = ReportFingerprint::make(
            source: $sourceSlug,
            environment: $environment,
            type: (string) $request->input('type'),
            title: (string) $request->input('title'),
            metadata: $meta,
        );
        $canonical = $fingerprint
            ? Report::query()
                ->whereNull('occurrence_parent_id')
                ->where('source_slug', $sourceSlug)
                ->where('environment', $environment)
                ->where('fingerprint', $fingerprint)
                ->first()
            : null;

        $report = Report::create([
            'source_slug' => $sourceSlug,
            'reporter_installation_id' => $token['reporter_installation_id'],
            'occurrence_parent_id' => $canonical?->id,
            'fingerprint' => $fingerprint,
            'environment' => $environment,
            'reporter_user_id' => $token['user_id'] ?? null,
            'reporter_email' => $token['user_email'],
            'reporter_name' => $token['user_name'] ?? null,
            'type' => $request->input('type'),
            'severity' => $request->input('severity'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'url' => $meta['url'] ?? '',
            'referrer' => $meta['referrer'] ?? null,
            'app_version' => $meta['app_version'] ?? null,
            'user_agent' => $meta['user_agent'] ?? null,
            'browser' => $ua['browser'],
            'browser_version' => $ua['browser_version'],
            'os' => $ua['os'],
            'os_version' => $ua['os_version'],
            'device' => $ua['device'],
            'screen_wh' => $meta['screen_wh'] ?? null,
            'viewport_wh' => $meta['viewport_wh'] ?? null,
            'dpr' => $meta['dpr'] ?? null,
            'timezone' => $meta['timezone'] ?? null,
            'locale' => $meta['locale'] ?? null,
            'client_events' => $meta['client_events'] ?? [],
            'network_log' => $meta['network_log'] ?? [],
            'laravel_context' => [],
        ]);

        $this->storeAttachment($report, $request->file('snapshot'), 'auto_screenshot');
        foreach ((array) $request->file('attachments') as $f) {
            $this->storeAttachment($report, $f, 'user_upload');
        }

        $debugIds = $meta['debug_ids'] ?? [];
        if (! is_array($debugIds)) {
            $debugIds = [];
        }

        $ids = collect($debugIds)
            ->filter(fn (mixed $id): bool => is_string($id) && Str::isUuid($id))
            ->unique()
            ->take(50)
            ->values()
            ->all();
        $installation = $token['reporter_installation_id']
            ? ReporterInstallation::find($token['reporter_installation_id'])
            : null;
        $hydrated = $this->fetcher->fetch($installation ?? $token['source_slug'], $ids);
        $report->laravel_context = array_map(fn ($id) => ['id' => $id, 'context' => $hydrated[$id] ?? null], $ids);
        $report->save();

        if ($canonical) {
            $severityRanks = ['low' => 0, 'med' => 1, 'high' => 2, 'blocker' => 3];
            $updates = ['archived_at' => null, 'updated_at' => now()];
            if (($severityRanks[$report->severity] ?? 0) > ($severityRanks[$canonical->severity] ?? 0)) {
                $updates['severity'] = $report->severity;
            }
            $canonical->update($updates);
        }

        Notification::send(User::all(), new ReportReceived($report));

        return response()->json(['report_id' => $report->id], 201);
    }

    private function storeAttachment(Report $report, ?UploadedFile $file, string $kind): void
    {
        if (! $file) {
            return;
        }
        $disk = config('filesystems.default');
        $path = $file->storeAs("reports/{$report->id}", $file->hashName(), $disk);
        ReportAttachment::create([
            'report_id' => $report->id,
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'kind' => $kind,
        ]);
    }
}
