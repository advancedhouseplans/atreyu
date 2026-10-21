<?php

use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Livewire\Tasks\Show;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('updates task details and adds a comment', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['status' => TaskStatus::Backlog, 'difficulty' => null]);

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->call('updateStatus', TaskStatus::InProgress->value)
        ->call('updateDifficulty', TaskDifficulty::Four->value)
        ->set('newComment', 'Parts have been ordered.')
        ->call('addComment')
        ->assertHasNoErrors()
        ->assertSee('Parts have been ordered.');

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->difficulty)->toBe(TaskDifficulty::Four);

    $this->assertDatabaseHas('task_comments', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Parts have been ordered.',
    ]);
});

it('rejects an empty comment', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->set('newComment', '')
        ->call('addComment')
        ->assertHasErrors(['newComment' => 'required']);

    expect($task->comments()->count())->toBe(0);
});

it('edits core task fields and records the activity', function () {
    $user = User::factory()->create();
    $newProject = Project::factory()->create();
    $task = Task::factory()->create(['title' => 'Old title']);

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->set('editTitle', 'Order the workstation')
        ->set('editDescription', 'Order and configure it before Monday.')
        ->set('editProjectId', (string) $newProject->id)
        ->set('editType', TaskType::Build->value)
        ->set('editPriority', TaskPriority::High->value)
        ->set('editDueDate', '2026-11-02')
        ->call('editTask')
        ->assertHasNoErrors()
        ->assertSee('Order the workstation')
        ->assertSee('updated project, title, description, priority and due date');

    $task->refresh();
    expect($task->project_id)->toBe($newProject->id)
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($task->due_date?->toDateString())->toBe('2026-11-02')
        ->and($task->identifier)->toBe('AT-'.$task->id);
});

it('archives and reopens a task without deleting it', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $component = Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->call('archiveTask')
        ->assertSee('Archived');

    expect($task->fresh()->archived_at)->not->toBeNull();

    $component->call('reopenTask')->assertDontSee('Archived');

    expect($task->fresh()->archived_at)->toBeNull()
        ->and($task->activities()->pluck('event')->all())->toBe(['archived', 'reopened']);
});

it('renames a task from the inline title editor', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['title' => 'Old title']);

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->call('updateTitle', '  Order the workstation  ')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($task->fresh()->title)->toBe('Order the workstation');
});

it('rejects a blank inline title and keeps the old one', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['title' => 'Old title']);

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->call('updateTitle', '   ')
        ->assertHasErrors(['title' => 'required']);

    expect($task->fresh()->title)->toBe('Old title');
});

it('changes priority from the inline editor', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['priority' => TaskPriority::Normal]);

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->call('updatePriority', 'urgent')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($task->fresh()->priority)->toBe(TaskPriority::Urgent);
});

it('rejects an unknown priority', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['priority' => TaskPriority::Normal]);

    Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->call('updatePriority', 'extreme')
        ->assertHasErrors(['priority']);

    expect($task->fresh()->priority)->toBe(TaskPriority::Normal);
});

it('treats a cleared inline title as blank', function () {
    $task = Task::factory()->create(['title' => 'Old title']);

    Livewire::actingAs(User::factory()->create())->test(Show::class, ['task' => $task])
        ->call('updateTitle', null)
        ->assertHasErrors(['title' => 'required']);

    expect($task->fresh()->title)->toBe('Old title');
});

it('rejects a cleared inline priority', function () {
    $task = Task::factory()->create(['priority' => TaskPriority::High]);

    Livewire::actingAs(User::factory()->create())->test(Show::class, ['task' => $task])
        ->call('updatePriority', null)
        ->assertHasErrors(['priority']);

    expect($task->fresh()->priority)->toBe(TaskPriority::High);
});

it('aborts with 422 when the inline status is cleared', function () {
    $task = Task::factory()->create(['status' => TaskStatus::Review]);

    Livewire::actingAs(User::factory()->create())->test(Show::class, ['task' => $task])
        ->call('updateStatus', null)
        ->assertStatus(422);

    expect($task->fresh()->status)->toBe(TaskStatus::Review);
});

it('renders task descriptions as safe markdown', function () {
    $task = Task::factory()->create([
        'description' => "## Setup\n\n- Install updates\n- **Restart**\n\n<script>alert('nope')</script>",
    ]);

    Livewire::actingAs(User::factory()->create())->test(Show::class, ['task' => $task])
        ->assertSeeHtml('<h2>Setup</h2>')
        ->assertSeeHtml('<strong>Restart</strong>')
        ->assertDontSeeHtml('<script>');
});

it('creates and completes a nested subtask without adding it to the top-level board order', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user, 'assignee')->create();

    $component = Livewire::actingAs($user)->test(Show::class, ['task' => $task])
        ->set('newSubtaskTitle', 'Install system updates')
        ->call('addSubtask')
        ->assertHasNoErrors()
        ->assertSee('Install system updates');

    $subtask = Task::where('parent_id', $task->id)->sole();
    expect($subtask->project_id)->toBe($task->project_id)
        ->and($subtask->assignee_id)->toBe($user->id)
        ->and($subtask->status)->toBe(TaskStatus::Backlog);

    $component->call('toggleSubtask', $subtask->id)->assertSee('1/1 complete');
    expect($subtask->fresh()->status)->toBe(TaskStatus::Done);
});

it('notifies a newly assigned user but not someone assigning a task to themselves', function () {
    Notification::fake();
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $task = Task::factory()->create(['assignee_id' => null]);

    Livewire::actingAs($actor)->test(Show::class, ['task' => $task])
        ->call('updateAssignee', $assignee->id)
        ->call('updateAssignee', $actor->id);

    Notification::assertSentTo($assignee, TaskAssigned::class);
    Notification::assertNotSentTo($actor, TaskAssigned::class);
});
