<?php

namespace App\Livewire\Reports;

use App\Actions\PromoteReportToTask;
use App\Enums\TaskDifficulty;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Report;
use App\Models\ReportNote;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Report $report;

    public string $newNote = '';

    public bool $showTriage = false;

    public string $triageTitle = '';

    public string $triageProjectId = '';

    public string $triageAssigneeId = '';

    public string $triageType = 'bug';

    public string $triageDifficulty = '';

    public function mount(Report $report): void
    {
        $this->report = $report->load(['notes.author', 'attachments', 'assignee', 'task']);
    }

    public function openTriage(): void
    {
        if ($this->report->task) {
            $this->redirectRoute('tasks.show', ['task' => $this->report->task], navigate: true);

            return;
        }

        $matchingProject = Project::where('slug', $this->report->source_slug)->first();

        $this->triageTitle = $this->report->title;
        $this->triageProjectId = $matchingProject ? (string) $matchingProject->id : 'source';
        $this->triageAssigneeId = $this->report->assignee_id ? (string) $this->report->assignee_id : '';
        $this->triageType = $this->report->type === 'bug' ? TaskType::Bug->value : TaskType::Build->value;
        $this->triageDifficulty = '';
        $this->resetValidation();
        $this->showTriage = true;
    }

    public function promoteToTask(PromoteReportToTask $promoter): void
    {
        $projectIds = Project::query()->pluck('id')->map(fn (int $id): string => (string) $id)->all();
        $validated = $this->validate([
            'triageTitle' => ['required', 'string', 'max:255'],
            'triageProjectId' => ['required', Rule::in(['source', ...$projectIds])],
            'triageAssigneeId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'triageType' => ['required', Rule::enum(TaskType::class)],
            'triageDifficulty' => ['nullable', 'integer', Rule::in(array_map(fn (TaskDifficulty $difficulty): int => $difficulty->value, TaskDifficulty::cases()))],
        ]);

        $project = $validated['triageProjectId'] === 'source'
            ? null
            : Project::findOrFail((int) $validated['triageProjectId']);
        $assignee = filled($validated['triageAssigneeId'])
            ? User::findOrFail((int) $validated['triageAssigneeId'])
            : null;

        /** @var User $actor */
        $actor = auth()->user();

        $task = $promoter->handle(
            report: $this->report,
            actor: $actor,
            project: $project,
            assignee: $assignee,
            type: TaskType::from($validated['triageType']),
            difficulty: filled($validated['triageDifficulty']) ? TaskDifficulty::from((int) $validated['triageDifficulty']) : null,
            title: $validated['triageTitle'],
        );

        $this->redirectRoute('tasks.show', ['task' => $task], navigate: true);
    }

    public function dismissReport(): void
    {
        abort_if($this->report->task()->exists(), 422);

        $this->report->update(['status' => 'wontfix']);
        $this->showTriage = false;
    }

    public function reopenReport(): void
    {
        abort_if($this->report->task()->exists(), 422);

        $this->report->update(['status' => 'new']);
    }

    public function updateStatus(?string $status): void
    {
        abort_unless(in_array((string) $status, ['new', 'in_progress', 'done', 'wontfix'], true), 422);
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

    public function updateTitle(?string $title): void
    {
        $validated = Validator::make(['title' => trim((string) $title)], ['title' => ['required', 'string', 'max:255']])->validate();

        $this->report->update(['title' => $validated['title']]);
        $this->dispatch('toast', message: 'Title updated', variant: 'success');
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
            'projects' => Project::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'taskTypes' => TaskType::cases(),
            'taskDifficulties' => TaskDifficulty::cases(),
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
