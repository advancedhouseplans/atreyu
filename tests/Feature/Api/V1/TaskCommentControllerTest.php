<?php

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns 401 when no token is provided', function () {
    $task = Task::factory()->create();

    $this->postJson("/api/v1/tasks/{$task->id}/comments", ['body' => 'Hello'])
        ->assertUnauthorized();
});

it('creates a comment attributed to the authenticated user', function () {
    $user = User::factory()->create();
    $spoofedAuthor = User::factory()->create();
    $task = Task::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/tasks/{$task->id}/comments", [
        'body' => 'I can reproduce this.',
        'user_id' => $spoofedAuthor->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.body', 'I can reproduce this.')
        ->assertJsonPath('data.author.id', $user->id);

    $this->assertDatabaseHas('task_comments', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'I can reproduce this.',
    ]);
});

it('returns 422 and creates nothing for an empty comment', function () {
    $task = Task::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/tasks/{$task->id}/comments", ['body' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
    $this->assertDatabaseCount('task_comments', 0);
});
