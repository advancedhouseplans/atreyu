<?php

namespace App\Livewire\Reports;

use App\Models\Report;
use App\Models\ReportNote;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Report $report;

    public string $newNote = '';

    public function mount(Report $report): void
    {
        $this->report = $report->load(['notes.author', 'attachments', 'assignee']);
    }

    public function updateStatus(string $status): void
    {
        abort_unless(in_array($status, ['new', 'in_progress', 'done', 'wontfix'], true), 422);
        $this->report->update(['status' => $status]);
    }

    public function updateAssignee(?int $userId): void
    {
        if ($userId && ! User::whereKey($userId)->exists()) {
            return;
        }
        $this->report->update(['assignee_id' => $userId]);
        $this->report->load('assignee');
    }

    public function updateTitle(string $title): void
    {
        $t = trim($title);
        if ($t === '') {
            return;
        }
        $this->report->update(['title' => $t]);
    }

    public function addNote(): void
    {
        $body = trim($this->newNote);
        if ($body === '') {
            return;
        }
        ReportNote::create([
            'report_id' => $this->report->id,
            'user_id' => auth()->id(),
            'body' => $body,
        ]);
        $this->newNote = '';
        $this->report->load('notes.author');
    }

    public function render(): View
    {
        return view('livewire.reports.show', [
            'users' => User::orderBy('name')->get(),
            'timeline' => $this->buildTimeline(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildTimeline(): array
    {
        $events = collect($this->report->client_events ?? [])->map(fn ($e) => $e + ['kind' => 'event']);
        $nets = collect($this->report->network_log ?? [])->map(fn ($n) => $n + ['kind' => 'network']);
        $ctxs = collect($this->report->laravel_context ?? [])->map(fn ($c) => $c + ['kind' => 'laravel']);

        return $events->concat($nets)->concat($ctxs)
            ->sortByDesc(fn ($x) => $x['ts'] ?? 0)
            ->values()
            ->all();
    }
}
