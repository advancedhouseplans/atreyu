<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MoveTask;
use App\Actions\NotifyTaskAssignment;
use App\Actions\RecordTaskActivity;
use App\Actions\UpdateTaskDetails;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexTaskRequest;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $sort = $request->validated('sort', 'created_at');
        $direction = $request->validated('direction', 'desc');

        $tasks = Task::query()
            ->with(['project', 'assignee', 'creator', 'report'])
            ->withCount(['comments', 'subtasks'])
            ->when($request->validated('scope') === 'mine', fn ($query) => $query->whereBelongsTo($request->user(), 'assignee'))
            ->when($request->validated('scope') === 'unassigned', fn ($query) => $query->whereNull('assignee_id'))
            ->when($request->validated('project_id'), fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->validated('assignee_id'), fn ($query, $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->validated('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->validated('priority'), fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->validated('q'), function ($query, $search): void {
                $numericId = preg_match('/(?:^|-)\s*(\d+)$/', $search, $matches) ? (int) $matches[1] : null;
                $query->where(fn ($query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->when($numericId, fn ($query) => $query->orWhereKey($numericId)));
            })
            ->when(
                $request->validated('visibility', 'active'),
                fn ($query, $visibility) => match ($visibility) {
                    'archived' => $query->whereNotNull('archived_at'),
                    'all' => $query,
                    default => $query->whereNull('archived_at'),
                },
            );

        if ($sort === 'priority') {
            $direction === 'asc'
                ? $tasks->orderByRaw("CASE priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'normal' THEN 2 ELSE 1 END ASC")
                : $tasks->orderByRaw("CASE priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'normal' THEN 2 ELSE 1 END DESC");
        } elseif ($sort === 'position') {
            $tasks->orderBy('position', $direction)->orderBy('id', $direction);
        } else {
            $tasks->orderBy($sort, $direction)->orderBy('id', $direction);
        }

        return TaskResource::collection(
            $tasks->paginate($request->integer('per_page', 25))->withQueryString(),
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = TaskStatus::tryFrom($validated['status'] ?? '') ?? TaskStatus::Backlog;
        $parent = filled($validated['parent_id'] ?? null)
            ? Task::query()->whereKey((int) $validated['parent_id'])->firstOrFail()
            : null;

        if ($parent && $parent->project_id !== (int) $validated['project_id']) {
            throw ValidationException::withMessages(['parent_id' => 'The subtask must belong to the same project as its parent.']);
        }

        $task = Task::create([
            ...$validated,
            'creator_id' => $request->user()->id,
            'priority' => $validated['priority'] ?? TaskPriority::Normal,
            'status' => $status,
            'position' => Task::nextPosition($status, $parent?->id),
        ]);
        app(RecordTaskActivity::class)->handle($task, 'created', $request->user());
        app(NotifyTaskAssignment::class)->handle($task, $request->user());

        return (new TaskResource($this->loadTask($task)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($this->loadTask($task, true));
    }

    public function update(UpdateTaskRequest $request, Task $task, MoveTask $moveTask, UpdateTaskDetails $updateTask): TaskResource
    {
        $validated = $request->validated();
        $status = array_key_exists('status', $validated)
            ? TaskStatus::from($validated['status'])
            : null;
        $archived = array_key_exists('archived', $validated) ? (bool) $validated['archived'] : null;

        unset($validated['status'], $validated['archived']);
        $updateTask->handle($task, $validated, $request->user());

        if ($status && $status !== $task->status) {
            $moveTask->handle($task, $status, actor: $request->user());
        }

        if ($archived !== null && $archived !== (bool) $task->archived_at) {
            $task->update(['archived_at' => $archived ? now() : null]);
            app(RecordTaskActivity::class)->handle($task, $archived ? 'archived' : 'reopened', $request->user());
        }

        return new TaskResource($this->loadTask($task, true));
    }

    private function loadTask(Task $task, bool $withComments = false): Task
    {
        $relationships = ['project', 'assignee', 'creator', 'report'];

        if ($withComments) {
            $relationships[] = 'comments.author';
            $relationships[] = 'activities.actor';
        }

        return $task->load($relationships)->loadCount(['comments', 'subtasks']);
    }
}
