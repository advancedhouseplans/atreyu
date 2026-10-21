<?php

use App\Livewire\Reports\Show;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders header and meta', function () {
    $u = User::factory()->create();
    $r = Report::factory()->create(['title' => 'Broken button', 'browser' => 'Chrome']);

    Livewire::actingAs($u)->test(Show::class, ['report' => $r])
        ->assertSee('Broken button')
        ->assertSee('Chrome');
});

it('updates status', function () {
    $u = User::factory()->create();
    $r = Report::factory()->create(['status' => 'new']);

    Livewire::actingAs($u)->test(Show::class, ['report' => $r])
        ->call('updateStatus', 'in_progress')
        ->assertSet('report.status', 'in_progress');

    expect($r->fresh()->status)->toBe('in_progress');
});

it('adds a note', function () {
    $u = User::factory()->create();
    $r = Report::factory()->create();

    Livewire::actingAs($u)->test(Show::class, ['report' => $r])
        ->set('newNote', 'looking into it')
        ->call('addNote')
        ->assertSee('looking into it');

    expect($r->fresh()->notes)->toHaveCount(1);
});

it('creates a task from a report', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();
    $report = Report::factory()->create(['title' => 'Broken checkout']);

    Livewire::actingAs($user)->test(Show::class, ['report' => $report])
        ->call('openTriage')
        ->assertSet('showTriage', true)
        ->set('triageTitle', 'Checkout fails after login')
        ->set('triageProjectId', (string) $project->id)
        ->set('triageAssigneeId', (string) $assignee->id)
        ->set('triageType', 'bug')
        ->set('triageDifficulty', '3')
        ->call('promoteToTask');

    $task = Task::whereBelongsTo($report)->firstOrFail();

    $this->assertModelExists($task);
    expect($task->title)->toBe('Checkout fails after login')
        ->and($task->project->is($project))->toBeTrue()
        ->and($task->assignee->is($assignee))->toBeTrue()
        ->and($task->difficulty?->value)->toBe(3);
});

it('creates a source project when triage finds no existing match', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create(['source_slug' => 'clara-desktop']);

    Livewire::actingAs($user)->test(Show::class, ['report' => $report])
        ->call('openTriage')
        ->assertSet('triageProjectId', 'source')
        ->call('promoteToTask');

    $this->assertDatabaseHas('projects', [
        'slug' => 'clara-desktop',
        'name' => 'Clara Desktop',
    ]);
});

it('dismisses a report without creating a task', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create(['status' => 'new']);

    Livewire::actingAs($user)->test(Show::class, ['report' => $report])
        ->call('openTriage')
        ->call('dismissReport')
        ->assertSet('report.status', 'wontfix')
        ->assertSet('showTriage', false);

    expect($report->fresh()->task)->toBeNull();
});

it('rejects triage values that do not belong to known records', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create();

    Livewire::actingAs($user)->test(Show::class, ['report' => $report])
        ->call('openTriage')
        ->set('triageProjectId', '999999')
        ->set('triageAssigneeId', '999999')
        ->set('triageDifficulty', '9')
        ->call('promoteToTask')
        ->assertHasErrors(['triageProjectId', 'triageAssigneeId', 'triageDifficulty']);

    expect($report->fresh()->task)->toBeNull();
});
