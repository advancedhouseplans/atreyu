<?php

use App\Actions\MoveTask;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

it('moves a task between lanes and reorders both lanes', function () {
    $firstBacklog = Task::factory()->create(['status' => TaskStatus::Backlog, 'position' => 0]);
    $movedTask = Task::factory()->recycle($firstBacklog->project)->create(['status' => TaskStatus::Backlog, 'position' => 1]);
    $lastBacklog = Task::factory()->recycle($firstBacklog->project)->create(['status' => TaskStatus::Backlog, 'position' => 2]);
    $existingDone = Task::factory()->recycle($firstBacklog->project)->create(['status' => TaskStatus::Done, 'position' => 0]);

    app(MoveTask::class)->handle($movedTask, TaskStatus::Done, 0);

    expect($movedTask->status)->toBe(TaskStatus::Done)
        ->and($movedTask->position)->toBe(0)
        ->and($existingDone->fresh()->position)->toBe(1)
        ->and($firstBacklog->fresh()->position)->toBe(0)
        ->and($lastBacklog->fresh()->position)->toBe(1);
});

it('records status changes without recording simple reordering', function () {
    $actor = User::factory()->create();
    $task = Task::factory()->create(['status' => TaskStatus::Backlog]);

    app(MoveTask::class)->handle($task, TaskStatus::InProgress, actor: $actor);
    app(MoveTask::class)->handle($task, TaskStatus::InProgress, 0, $actor);

    expect($task->activities()->count())->toBe(1)
        ->and($task->activities()->first()->event)->toBe('status_changed')
        ->and($task->activities()->first()->actor_id)->toBe($actor->id);
});
