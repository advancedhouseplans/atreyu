<?php

namespace App\Livewire\Projects;

use App\Actions\CreateReporterInstallation;
use App\Models\Project;
use App\Models\ReporterInstallation;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithPagination;

    public Project $project;

    public bool $showConnect = false;

    public bool $showCredential = false;

    public bool $showRepository = false;

    public string $environment = 'local';

    public string $issuedCredential = '';

    public string $repositoryUrl = '';

    #[Url]
    public string $tab = 'reports';

    #[Url]
    public string $taskAssignee = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->showConnect = request()->boolean('connect') && ! $project->connection()->exists();
        $this->repositoryUrl = $project->repository_url ?? '';
    }

    public function createConnection(CreateReporterInstallation $creator): void
    {
        if ($this->project->connection()->exists()) {
            $this->addError('environment', 'This project already has a connection.');

            return;
        }

        $validated = $this->validate([
            'environment' => ['required', Rule::in(['local', 'staging', 'production'])],
        ]);

        /** @var User $actor */
        $actor = auth()->user();
        $result = $creator->handle($this->project, $actor, $this->project->name, $validated['environment']);

        $this->showConnect = false;
        $this->issuedCredential = $result['credential'];
        $this->showCredential = true;
    }

    public function rotateConnection(CreateReporterInstallation $creator): void
    {
        $connection = $this->connection();

        $this->issuedCredential = $creator->rotate($connection);
        $this->showCredential = true;
    }

    public function revokeConnection(): void
    {
        $this->connection()->update(['revoked_at' => now()]);
    }

    public function updatedShowCredential(bool $value): void
    {
        if (! $value) {
            $this->clearCredential();
        }
    }

    public function clearCredential(): void
    {
        $this->issuedCredential = '';
        $this->showCredential = false;
    }

    public function saveRepository(): void
    {
        $validated = $this->validate([
            'repositoryUrl' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $this->project->update(['repository_url' => rtrim($validated['repositoryUrl'], '/')]);

        $this->showRepository = false;
        $this->dispatch('toast', message: 'Repository saved', variant: 'success');
    }

    public function removeRepository(): void
    {
        $this->project->update(['repository_url' => null]);
        $this->repositoryUrl = '';
        $this->showRepository = false;
        $this->dispatch('toast', message: 'Repository removed', variant: 'success');
    }

    public function updatingTab(): void
    {
        $this->resetPage('workPage');
    }

    public function updatingTaskAssignee(): void
    {
        $this->resetPage('workPage');
    }

    public function render(): View
    {
        return view('livewire.projects.show', [
            'connection' => $this->project->connection()->withCount('reports')->first(),
            'users' => User::orderBy('name')->get(),
            'workItems' => $this->workItems(),
        ]);
    }

    private function connection(): ReporterInstallation
    {
        return $this->project->connection()->firstOrFail();
    }

    /** @return LengthAwarePaginator<int, object> */
    private function workItems(): LengthAwarePaginator
    {
        $reports = DB::table('reports')
            ->where('source_slug', $this->project->slug)
            ->whereNull('occurrence_parent_id')
            ->selectRaw("reports.id, 'report' as kind, reports.title, reports.description, reports.type, reports.severity as priority, NULL as status, COALESCE(reports.reporter_name, reports.reporter_email, 'Anonymous') as person, reports.archived_at, reports.created_at as sort_date");

        $tasks = DB::table('tasks')
            ->leftJoin('users', 'users.id', '=', 'tasks.assignee_id')
            ->where('tasks.project_id', $this->project->id)
            ->whereNull('tasks.parent_id')
            ->when($this->taskAssignee === 'unassigned', fn ($query) => $query->whereNull('tasks.assignee_id'))
            ->when(ctype_digit($this->taskAssignee), fn ($query) => $query->where('tasks.assignee_id', (int) $this->taskAssignee))
            ->selectRaw("tasks.id, 'task' as kind, tasks.title, tasks.description, tasks.type, tasks.priority, tasks.status, COALESCE(users.name, 'Unassigned') as person, tasks.archived_at, tasks.updated_at as sort_date");

        $work = $this->tab === 'tasks' ? $tasks : $reports;

        return DB::query()
            ->fromSub($work, 'project_work')
            ->orderByDesc('sort_date')
            ->orderByDesc('id')
            ->paginate(25, pageName: 'workPage');
    }
}
