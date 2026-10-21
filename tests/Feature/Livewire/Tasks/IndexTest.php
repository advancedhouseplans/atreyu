<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Livewire\Tasks\Index;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('creates a manual task in a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('title', 'Build a new PC')
        ->set('description', 'For the new employee')
        ->set('projectId', (string) $project->id)
        ->set('assigneeId', (string) $user->id)
        ->set('type', TaskType::Build->value)
        ->set('difficulty', '3')
        ->set('priority', TaskPriority::High->value)
        ->set('dueDate', '2026-11-02')
        ->call('createTask')
        ->assertHasNoErrors()
        ->assertSee('Build a new PC');

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'assignee_id' => $user->id,
        'creator_id' => $user->id,
        'title' => 'Build a new PC',
        'type' => 'build',
        'difficulty' => 3,
        'priority' => 'high',
    ]);
    expect(Task::firstOrFail()->due_date?->toDateString())->toBe('2026-11-02');
    $this->assertDatabaseHas('task_activities', ['event' => 'created', 'actor_id' => $user->id]);
});

it('shows only tasks assigned to the current user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Task::factory()->for($user, 'assignee')->create(['title' => 'Mine to finish']);
    Task::factory()->for($otherUser, 'assignee')->create(['title' => 'Someone else owns this']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('scope', 'mine')
        ->assertSee('Mine to finish')
        ->assertDontSee('Someone else owns this');
});

it('requires authentication through the route', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});

it('renders all kanban lanes and persists a dragged task status', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['status' => TaskStatus::Backlog, 'position' => 0]);

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee('Backlog')
        ->assertSee('In progress')
        ->assertSee('Review')
        ->assertSee('Done')
        ->call('moveTask', $task->id, 0, TaskStatus::InProgress->value)
        ->assertHasNoErrors();

    expect($task->fresh()->status)->toBe(TaskStatus::InProgress);
});

it('searches active tasks and keeps archived work out of the default view', function () {
    $user = User::factory()->create();
    $active = Task::factory()->create(['title' => 'Replace accounting PC']);
    $archived = Task::factory()->create(['title' => 'Replace archived PC', 'archived_at' => now()]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('display', 'list')
        ->set('q', 'Replace')
        ->assertSee($active->title)
        ->assertDontSee($archived->title)
        ->set('visibility', 'archived')
        ->assertDontSee($active->title)
        ->assertSee($archived->title);
});

it('clears individual task filters and all filters', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Clara']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('display', 'list')
        ->set('scope', 'mine')
        ->set('status', TaskStatus::Review->value)
        ->set('project', (string) $project->id)
        ->set('priorityFilter', TaskPriority::High->value)
        ->set('visibility', 'archived')
        ->assertSee('Clara')
        ->assertSee('Archived')
        ->call('clearFilter', 'project')
        ->assertSet('project', '')
        ->call('clearFilters')
        ->assertSet('scope', 'all')
        ->assertSet('status', '')
        ->assertSet('priorityFilter', '')
        ->assertSet('visibility', 'active');
});

it('defaults a quick-created task to the current user', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->call('openCreate')
        ->assertSet('showCreate', true)
        ->assertSet('assigneeId', (string) $user->id);
});

it('shows an empty state in the list view when nothing matches', function () {
    Livewire::actingAs(User::factory()->create())->test(Index::class)
        ->set('display', 'list')
        ->assertSee('Nothing to do?');
});

it('renders board card details for an urgent assigned task', function () {
    $user = User::factory()->create();
    Task::factory()->for($user, 'assignee')->create([
        'title' => 'Fix the VPN',
        'priority' => TaskPriority::Urgent,
        'due_date' => now()->subDay(),
    ]);

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee('Fix the VPN')
        ->assertSee('Urgent')
        ->assertSee('Move Fix the VPN');
});

it('escapes a task title once in the board handle label', function () {
    $user = User::factory()->create();
    Task::factory()->create(['title' => "O'Brien & Co"]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('scope', 'all')
        ->assertSeeHtml('aria-label="Move O&#039;Brien &amp; Co"');
});

it('keeps subtasks nested instead of duplicating them on the main task board', function () {
    $parent = Task::factory()->create(['title' => 'Prepare workstation']);
    Task::factory()->create([
        'parent_id' => $parent->id,
        'project_id' => $parent->project_id,
        'title' => 'Install updates',
    ]);

    Livewire::actingAs(User::factory()->create())->test(Index::class)
        ->assertSee('Prepare workstation')
        ->assertDontSee('Install updates');
});
