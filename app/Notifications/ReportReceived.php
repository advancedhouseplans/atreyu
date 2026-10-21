<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportReceived extends Notification
{
    use Queueable;

    public function __construct(public readonly Report $report) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $canonical = $this->report->canonicalReport();

        return [
            'kind' => 'report_received',
            'title' => ($this->report->occurrence_parent_id ? 'New occurrence from ' : 'New report from ').str($this->report->source_slug)->headline(),
            'message' => $this->report->title,
            'url' => route('reports.show', ['report' => $canonical, 'occurrence' => $this->report->id], absolute: false),
            'report_id' => $this->report->id,
            'canonical_report_id' => $canonical->id,
        ];
    }
}
