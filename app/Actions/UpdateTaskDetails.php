<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;

class UpdateTaskDetails
{
    /** @param array<string, mixed> $attributes */
    public function handle(Task $task, array $attributes, ?User $actor = null): Task
    {
        $previousAssigneeId = $task->assignee_id;
        $task->fill($attributes);
        $dirty = collect(array_keys($task->getDirty()))
            ->reject(fn (string $field) => in_array($field, ['updated_at', 'position', 'status'], true))
            ->values()
            ->all();

        if ($task->isDirty()) {
            $task->save();
        }

        if (! $task->parent_id && in_array('project_id', $dirty, true)) {
            $task->subtasks()->update(['project_id' => $task->project_id]);
        }

        if ($dirty !== []) {
            app(RecordTaskActivity::class)->handle($task, 'details_updated', $actor, [
                'fields' => array_map(fn (string $field) => str($field)->replace('_id', '')->replace('_', ' ')->toString(), $dirty),
            ]);
        }

        if (in_array('assignee_id', $dirty, true) && $task->assignee_id !== $previousAssigneeId) {
            app(NotifyTaskAssignment::class)->handle($task, $actor);
        }

        return $task->refresh();
    }
}
