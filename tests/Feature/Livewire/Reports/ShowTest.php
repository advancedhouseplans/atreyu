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

it('rejects a blank inline title', function () {
    $report = Report::factory()->create(['title' => 'Original']);
    Livewire::actingAs(User::factory()->create())->test(Show::class, ['report' => $report])
        ->call('updateTitle', '   ')->assertHasErrors(['title' => 'required']);
    expect($report->refresh()->title)->toBe('Original');
});

it('saves an inline title and toasts', function () {
    $report = Report::factory()->create();
    Livewire::actingAs(User::factory()->create())->test(Show::class, ['report' => $report])
        ->call('updateTitle', 'Checkout 500 on submit')->assertHasNoErrors()->assertDispatched('toast');
    expect($report->refresh()->title)->toBe('Checkout 500 on submit');
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

it('archives and restores a report without creating a task', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create();

    Livewire::actingAs($user)->test(Show::class, ['report' => $report])
        ->call('openTriage')
        ->call('archiveReport')
        ->assertSet('showTriage', false)
        ->assertSet('report.archived_at', fn ($value) => $value !== null)
        ->call('restoreReport')
        ->assertSet('report.archived_at', null);

    expect($report->fresh()->task)->toBeNull()
        ->and($report->archived_at)->toBeNull();
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

it('treats a cleared inline title as blank', function () {
    $report = Report::factory()->create(['title' => 'Original']);
    Livewire::actingAs(User::factory()->create())->test(Show::class, ['report' => $report])
        ->call('updateTitle', null)->assertHasErrors(['title' => 'required']);
    expect($report->refresh()->title)->toBe('Original');
});
