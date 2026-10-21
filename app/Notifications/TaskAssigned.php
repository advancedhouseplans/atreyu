<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly ?User $actor = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'task_assigned',
            'title' => 'Task assigned to you',
            'message' => $this->task->identifier.' · '.$this->task->title,
            'url' => route('tasks.show', $this->task, absolute: false),
            'task_id' => $this->task->id,
            'actor_name' => $this->actor?->name,
        ];
    }
}
