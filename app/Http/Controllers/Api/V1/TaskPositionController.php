<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MoveTask;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTaskPositionRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;

class TaskPositionController extends Controller
{
    public function __invoke(UpdateTaskPositionRequest $request, Task $task, MoveTask $moveTask): TaskResource
    {
        $moveTask->handle(
            $task,
            TaskStatus::from($request->validated('status')),
            $request->validated('position'),
            $request->user(),
        );

        return new TaskResource(
            $task->load(['project', 'assignee', 'creator', 'report'])->loadCount('comments'),
        );
    }
}
