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
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Report $report;

    #[Url(as: 'occurrence')]
    public string $occurrenceId = '';

    public string $newNote = '';

    public bool $showTriage = false;

    public string $triageTitle = '';

    public string $triageProjectId = '';

    public string $triageAssigneeId = '';

    public string $triageType = 'bug';

    public string $triageDifficulty = '';

    public function mount(Report $report): void
    {
        $selectedId = $report->id;
        $this->report = $report->canonicalReport()->load(['notes.author', 'attachments', 'task', 'occurrences.attachments']);
        $this->occurrenceId = (string) request()->query('occurrence', $selectedId);
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
        $this->triageAssigneeId = '';
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

    public function archiveReport(): void
    {
        abort_if($this->report->task()->exists(), 422);

        $this->report->update(['archived_at' => now()]);
        $this->showTriage = false;
    }

    public function restoreReport(): void
    {
        abort_if($this->report->task()->exists(), 422);

        $this->report->update(['archived_at' => null]);
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
        $occurrences = collect([$this->report])
            ->concat($this->report->occurrences)
            ->sortByDesc('created_at')
            ->values();
        $selectedOccurrence = $occurrences->firstWhere('id', (int) $this->occurrenceId) ?? $this->report;

        return view('livewire.reports.show', [
            'projects' => Project::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'taskTypes' => TaskType::cases(),
            'taskDifficulties' => TaskDifficulty::cases(),
            'timeline' => $this->buildTimeline($selectedOccurrence),
            'occurrences' => $occurrences,
            'selectedOccurrence' => $selectedOccurrence,
            'affectedReporters' => $occurrences
                ->map(fn (Report $occurrence) => $occurrence->reporter_user_id ?: $occurrence->reporter_email)
                ->filter()
                ->unique()
                ->count(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildTimeline(Report $report): array
    {
        $events = collect($report->client_events ?? [])->map(fn ($e) => $e + ['kind' => 'event']);
        $nets = collect($report->network_log ?? [])->map(fn ($n) => $n + ['kind' => 'network']);
        $ctxs = collect($report->laravel_context ?? [])->map(fn ($c) => $c + ['kind' => 'laravel']);

        return $events->concat($nets)->concat($ctxs)
            ->sortByDesc(fn ($x) => $x['ts'] ?? 0)
            ->values()
            ->all();
    }
}
