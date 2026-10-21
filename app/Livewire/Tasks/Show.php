<?php

namespace App\Livewire\Tasks;

use App\Actions\MoveTask;
use App\Actions\NotifyTaskAssignment;
use App\Actions\RecordTaskActivity;
use App\Actions\UpdateTaskDetails;
use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Task $task;

    public string $newComment = '';

    public string $newSubtaskTitle = '';

    public bool $showEdit = false;

    public string $editTitle = '';

    public string $editDescription = '';

    public string $editProjectId = '';

    public string $editType = '';

    public string $editPriority = '';

    public string $editDueDate = '';

    public function mount(Task $task): void
    {
        $this->task = $task;
        $this->refreshTask();
        $this->fillEditForm();
    }

    public function updateStatus(?string $status, MoveTask $moveTask): void
    {
        $taskStatus = TaskStatus::tryFrom((string) $status);
        abort_unless($taskStatus !== null, 422);

        $moveTask->handle($this->task, $taskStatus, actor: auth()->user());
        $this->refreshTask();
    }

    public function updateTitle(?string $title, UpdateTaskDetails $updateTask): void
    {
        $validated = Validator::make(['title' => trim((string) $title)], ['title' => ['required', 'string', 'max:255']])->validate();

        $updateTask->handle($this->task, ['title' => $validated['title']], auth()->user());
        $this->refreshTask();
        $this->fillEditForm();
        $this->dispatch('toast', message: 'Title updated', variant: 'success');
    }

    public function updatePriority(?string $priority, UpdateTaskDetails $updateTask): void
    {
        $validated = Validator::make(['priority' => (string) $priority], ['priority' => ['required', Rule::enum(TaskPriority::class)]])->validate();

        $updateTask->handle($this->task, ['priority' => $validated['priority']], auth()->user());
        $this->refreshTask();
        $this->fillEditForm();
        $this->dispatch('toast', message: 'Priority updated', variant: 'success');
    }

    public function updateAssignee(?int $userId, UpdateTaskDetails $updateTask): void
    {
        abort_if($userId && ! User::whereKey($userId)->exists(), 422);

        $updateTask->handle($this->task, ['assignee_id' => $userId], auth()->user());
        $this->refreshTask();
    }

    public function updateDifficulty(?int $difficulty, UpdateTaskDetails $updateTask): void
    {
        abort_if($difficulty && ! TaskDifficulty::tryFrom($difficulty), 422);

        $updateTask->handle($this->task, ['difficulty' => $difficulty], auth()->user());
        $this->refreshTask();
    }

    public function editTask(UpdateTaskDetails $updateTask): void
    {
        $validated = $this->validate([
            'editTitle' => ['required', 'string', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:10000'],
            'editProjectId' => ['required', 'integer', Rule::exists('projects', 'id')],
            'editType' => ['required', Rule::enum(TaskType::class)],
            'editPriority' => ['required', Rule::enum(TaskPriority::class)],
            'editDueDate' => ['nullable', 'date'],
        ]);

        $updateTask->handle($this->task, [
            'title' => $validated['editTitle'],
            'description' => $validated['editDescription'],
            'project_id' => $validated['editProjectId'],
            'type' => $validated['editType'],
            'priority' => $validated['editPriority'],
            'due_date' => filled($validated['editDueDate']) ? $validated['editDueDate'] : null,
        ], auth()->user());

        $this->showEdit = false;
        $this->refreshTask();
        $this->fillEditForm();
    }

    public function archiveTask(RecordTaskActivity $recordActivity): void
    {
        if ($this->task->archived_at) {
            return;
        }

        $this->task->update(['archived_at' => now()]);
        $recordActivity->handle($this->task, 'archived', auth()->user());
        $this->refreshTask();
    }

    public function reopenTask(RecordTaskActivity $recordActivity): void
    {
        if (! $this->task->archived_at) {
            return;
        }

        $this->task->update(['archived_at' => null]);
        $recordActivity->handle($this->task, 'reopened', auth()->user());
        $this->refreshTask();
    }

    public function addComment(): void
    {
        $validated = $this->validate([
            'newComment' => ['required', 'string', 'max:10000'],
        ]);

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
            'body' => $validated['newComment'],
        ]);

        $this->newComment = '';
        $this->refreshTask();
    }

    public function addSubtask(): void
    {
        abort_if($this->task->parent_id !== null, 422);

        $validated = $this->validate([
            'newSubtaskTitle' => ['required', 'string', 'max:255'],
        ]);

        $subtask = Task::create([
            'project_id' => $this->task->project_id,
            'parent_id' => $this->task->id,
            'assignee_id' => $this->task->assignee_id,
            'creator_id' => auth()->id(),
            'type' => $this->task->type,
            'status' => TaskStatus::Backlog,
            'position' => Task::nextPosition(TaskStatus::Backlog, $this->task->id),
            'priority' => $this->task->priority,
            'title' => $validated['newSubtaskTitle'],
        ]);

        app(RecordTaskActivity::class)->handle($subtask, 'created', auth()->user());
        app(NotifyTaskAssignment::class)->handle($subtask, auth()->user());

        $this->newSubtaskTitle = '';
        $this->refreshTask();
    }

    public function toggleSubtask(int $subtaskId, MoveTask $moveTask): void
    {
        $subtask = $this->task->subtasks()->findOrFail($subtaskId);
        $destination = $subtask->status === TaskStatus::Done ? TaskStatus::Backlog : TaskStatus::Done;

        $moveTask->handle($subtask, $destination, actor: auth()->user());
        $this->refreshTask();
    }

    public function render(): View
    {
        return view('livewire.tasks.show', [
            'users' => User::orderBy('name')->get(),
            'statuses' => TaskStatus::cases(),
            'difficulties' => TaskDifficulty::cases(),
            'priorities' => TaskPriority::cases(),
            'types' => TaskType::cases(),
            'projects' => Project::orderBy('name')->get(),
            'timeline' => collect($this->task->activities)
                ->map(fn ($activity) => ['kind' => 'activity', 'item' => $activity, 'created_at' => $activity->created_at])
                ->concat(collect($this->task->comments)->map(fn ($comment) => ['kind' => 'comment', 'item' => $comment, 'created_at' => $comment->created_at]))
                ->sortByDesc('created_at')
                ->values(),
        ]);
    }

    private function refreshTask(): void
    {
        $this->task->refresh()->load([
            'project', 'parent', 'assignee', 'creator', 'report', 'subtasks.assignee',
            'comments.author', 'activities.actor',
        ]);
    }

    private function fillEditForm(): void
    {
        $this->editTitle = $this->task->title;
        $this->editDescription = $this->task->description ?? '';
        $this->editProjectId = (string) $this->task->project_id;
        $this->editType = $this->task->type->value;
        $this->editPriority = $this->task->priority->value;
        $this->editDueDate = $this->task->due_date?->format('Y-m-d') ?? '';
    }
}
