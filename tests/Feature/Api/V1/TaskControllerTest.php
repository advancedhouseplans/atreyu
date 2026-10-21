<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/v1/tasks')->assertUnauthorized();
});

it('returns a paginated filtered task list for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create();
    Sanctum::actingAs($user);

    Task::factory()->for($project)->for($user, 'assignee')->create([
        'title' => 'Older matching task',
        'status' => TaskStatus::Backlog,
        'created_at' => now()->subDay(),
    ]);
    $newerTask = Task::factory()->for($project)->for($user, 'assignee')->create([
        'title' => 'Newer matching task',
        'status' => TaskStatus::Backlog,
    ]);
    Task::factory()->for($project)->for($otherUser, 'assignee')->create();
    Task::factory()->for($user, 'assignee')->create(['status' => TaskStatus::Done]);

    $response = $this->getJson("/api/v1/tasks?scope=mine&project_id={$project->id}&status=backlog&per_page=1");

    $response->assertOk()
        ->assertJsonPath('data.0.id', $newerTask->id)
        ->assertJsonPath('data.0.project.id', $project->id)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonCount(1, 'data');
    expect($response->json('links.next'))->toContain('scope=mine', 'status=backlog', 'per_page=1');
});

it('returns 422 for a sort column outside the allow list', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/tasks?sort=DROP%20TABLE%20tasks')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sort');
});

it('creates a task with the authenticated user as creator', function () {
    $user = User::factory()->create();
    $spoofedCreator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tasks', [
        'project_id' => $project->id,
        'assignee_id' => $assignee->id,
        'creator_id' => $spoofedCreator->id,
        'report_id' => 999,
        'position' => 999,
        'type' => TaskType::Build->value,
        'difficulty' => 3,
        'title' => 'Build a new employee PC',
        'description' => 'Order and assemble the workstation.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.creator.id', $user->id)
        ->assertJsonPath('data.position', 0)
        ->assertJsonPath('data.type', 'build')
        ->assertJsonPath('data.priority', 'normal');
    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'assignee_id' => $assignee->id,
        'creator_id' => $user->id,
        'report_id' => null,
        'position' => 0,
        'title' => 'Build a new employee PC',
    ]);
    $this->assertDatabaseHas('task_activities', [
        'task_id' => $response->json('data.id'),
        'actor_id' => $user->id,
        'event' => 'created',
    ]);
});

it('returns 422 and creates nothing when required task fields are missing', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/tasks', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id', 'type', 'title']);
    $this->assertDatabaseCount('tasks', 0);
});

it('returns task details with comments and its source report', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create([
        'source_slug' => 'takeoff',
        'reporter_email' => 'reporter@example.com',
    ]);
    $task = Task::factory()->for($report)->create(['title' => 'Broken estimate']);
    TaskComment::factory()->for($task)->for($user, 'author')->create(['body' => 'Reproduced']);
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Broken estimate')
        ->assertJsonPath('data.report.source_slug', 'takeoff')
        ->assertJsonPath('data.comments.0.body', 'Reproduced')
        ->assertJsonPath('data.comments.0.author.id', $user->id);
});

it('updates task fields and moves status through the board ordering action', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'status' => TaskStatus::Backlog,
        'position' => 0,
        'title' => 'Old title',
    ]);
    Task::factory()->create(['status' => TaskStatus::Review, 'position' => 0]);
    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/tasks/{$task->id}", [
        'title' => 'Ready for review',
        'status' => TaskStatus::Review->value,
        'difficulty' => null,
        'priority' => TaskPriority::Urgent->value,
        'due_date' => '2026-11-02',
        'archived' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Ready for review')
        ->assertJsonPath('data.status', 'review')
        ->assertJsonPath('data.position', 1)
        ->assertJsonPath('data.difficulty', null)
        ->assertJsonPath('data.priority', 'urgent')
        ->assertJsonPath('data.due_date', '2026-11-02')
        ->assertJsonPath('data.archived_at', fn ($value) => is_string($value));

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Ready for review',
        'status' => 'review',
        'position' => 1,
        'difficulty' => null,
        'priority' => 'urgent',
    ]);
    expect($task->activities()->pluck('event')->all())
        ->toBe(['details_updated', 'status_changed', 'archived']);
});
