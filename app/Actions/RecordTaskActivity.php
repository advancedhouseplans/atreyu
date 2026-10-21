<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;

class RecordTaskActivity
{
    /** @param array<string, mixed> $data */
    public function handle(Task $task, string $event, ?User $actor = null, array $data = []): TaskActivity
    {
        return $task->activities()->create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'data' => $data ?: null,
        ]);
    }
}
