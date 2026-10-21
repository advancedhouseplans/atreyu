<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns 401 when no token is provided', function () {
    $task = Task::factory()->create();

    $this->patchJson("/api/v1/tasks/{$task->id}/position", [
        'status' => 'review',
        'position' => 0,
    ])->assertUnauthorized();
});

it('moves a task to an exact board position and compacts both columns', function () {
    $user = User::factory()->create();
    $firstBacklog = Task::factory()->create(['status' => TaskStatus::Backlog, 'position' => 0]);
    $movingTask = Task::factory()->create(['status' => TaskStatus::Backlog, 'position' => 1]);
    $firstReview = Task::factory()->create(['status' => TaskStatus::Review, 'position' => 0]);
    $secondReview = Task::factory()->create(['status' => TaskStatus::Review, 'position' => 1]);
    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/tasks/{$movingTask->id}/position", [
        'status' => TaskStatus::Review->value,
        'position' => 1,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'review')
        ->assertJsonPath('data.position', 1);

    expect($firstBacklog->fresh()->position)->toBe(0)
        ->and($firstReview->fresh()->position)->toBe(0)
        ->and($movingTask->fresh()->position)->toBe(1)
        ->and($secondReview->fresh()->position)->toBe(2);
});

it('returns 422 for an invalid destination', function () {
    $task = Task::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson("/api/v1/tasks/{$task->id}/position", [
        'status' => 'waiting_on_the_nothing',
        'position' => -1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'position']);
});
