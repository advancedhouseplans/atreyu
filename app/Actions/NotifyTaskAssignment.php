<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;

class NotifyTaskAssignment
{
    public function handle(Task $task, ?User $actor = null): void
    {
        $assignee = $task->assignee()->first();

        if (! $assignee || $actor?->is($assignee)) {
            return;
        }

        $assignee->notify(new TaskAssigned($task, $actor));
    }
}
