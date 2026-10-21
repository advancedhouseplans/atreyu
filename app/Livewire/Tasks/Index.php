<?php

namespace App\Livewire\Tasks;

use App\Actions\MoveTask;
use App\Actions\RecordTaskActivity;
use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public bool $showCreate = false;

    public string $title = '';

    public string $description = '';

    public string $projectId = '';

    public string $assigneeId = '';

    public string $type = 'build';

    public string $difficulty = '';

    public string $priority = 'normal';

    public string $dueDate = '';

    #[Url]
    public string $q = '';

    #[Session(key: 'tasks.scope')]
    public string $scope = 'all';

    #[Url]
    public string $status = '';

    #[Url(as: 'project')]
    public string $project = '';

    #[Url]
    public string $priorityFilter = '';

    #[Url]
    public string $visibility = 'active';

    #[Url]
    public string $sortBy = 'updated_at';

    #[Url]
    public string $sortDir = 'desc';

    #[Session(key: 'tasks.display')]
    public string $display = 'board';

    public function mount(): void
    {
        if (request()->boolean('create')) {
            $this->openCreate();
        }
    }

    public function openCreate(): void
    {
        $this->assigneeId = (string) auth()->id();
        $this->showCreate = true;
    }

    public function createTask(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'projectId' => ['required', 'integer', Rule::exists('projects', 'id')],
            'assigneeId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'type' => ['required', Rule::enum(TaskType::class)],
            'difficulty' => ['nullable', 'integer', 'between:1,5'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'dueDate' => ['nullable', 'date'],
        ]);

        $task = Task::create([
            'project_id' => $validated['projectId'],
            'assignee_id' => filled($validated['assigneeId']) ? $validated['assigneeId'] : null,
            'creator_id' => auth()->id(),
            'type' => $validated['type'],
            'status' => TaskStatus::Backlog,
            'position' => Task::nextPosition(TaskStatus::Backlog),
            'difficulty' => filled($validated['difficulty']) ? $validated['difficulty'] : null,
            'priority' => $validated['priority'],
            'due_date' => filled($validated['dueDate']) ? $validated['dueDate'] : null,
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);
        app(RecordTaskActivity::class)->handle($task, 'created', auth()->user());

        $this->reset(['title', 'description', 'projectId', 'assigneeId', 'difficulty', 'dueDate', 'showCreate']);
        $this->type = TaskType::Build->value;
        $this->priority = TaskPriority::Normal->value;
        $this->resetPage();
    }

    public function updatedScope(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProject(): void
    {
        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedVisibility(): void
    {
        $this->resetPage();
    }

    public function updatedDisplay(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedSortDir(): void
    {
        $this->resetPage();
    }

    public function moveTask(int $taskId, int $position, string $status, MoveTask $moveTask): void
    {
        $destinationStatus = TaskStatus::tryFrom($status);
        abort_unless($destinationStatus !== null, 422);

        $task = $this->filteredTasksQuery()->findOrFail($taskId);
        $moveTask->handle($task, $destinationStatus, $position, auth()->user());
    }

    public function render(): View
    {
        $tasks = $this->display === 'list'
            ? $this->sortedListQuery()->paginate(25)
            : null;

        $boardTasks = $this->display === 'board'
            ? $this->filteredTasksQuery()
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (Task $task) => $task->status->value)
            : collect();

        return view('livewire.tasks.index', [
            'tasks' => $tasks,
            'boardTasks' => $boardTasks,
            'projects' => Project::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'types' => TaskType::cases(),
            'statuses' => TaskStatus::cases(),
            'difficulties' => TaskDifficulty::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    /** @return Builder<Task> */
    private function filteredTasksQuery(): Builder
    {
        return Task::query()
            ->with(['project', 'assignee', 'report'])
            ->when($this->scope === 'mine', fn ($query) => $query->where('assignee_id', auth()->id()))
            ->when($this->scope === 'unassigned', fn ($query) => $query->whereNull('assignee_id'))
            ->when($this->project, fn ($query) => $query->where('project_id', $this->project))
            ->when($this->priorityFilter, fn ($query) => $query->where('priority', $this->priorityFilter))
            ->when($this->display === 'list' && $this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->visibility === 'archived', fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->when($this->q, function ($query): void {
                $search = trim($this->q);
                $numericId = preg_match('/(?:^|-)\s*(\d+)$/', $search, $matches) ? (int) $matches[1] : null;
                $query->where(function ($query) use ($search, $numericId): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->when($numericId, fn ($query) => $query->orWhereKey($numericId));
                });
            });
    }

    /** @return Builder<Task> */
    private function sortedListQuery(): Builder
    {
        $query = $this->filteredTasksQuery();
        $direction = $this->sortDir === 'asc' ? 'asc' : 'desc';
        $allowed = ['updated_at', 'created_at', 'due_date', 'difficulty', 'title'];

        if ($this->sortBy === 'priority') {
            $direction === 'asc'
                ? $query->orderByRaw("CASE priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'normal' THEN 2 ELSE 1 END ASC")
                : $query->orderByRaw("CASE priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'normal' THEN 2 ELSE 1 END DESC");
        } else {
            $query->orderBy(in_array($this->sortBy, $allowed, true) ? $this->sortBy : 'updated_at', $direction);
        }

        return $query->orderBy('id', $direction);
    }
}
