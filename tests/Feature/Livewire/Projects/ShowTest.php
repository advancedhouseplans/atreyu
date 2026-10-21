<?php

use App\Livewire\Projects\Show;
use App\Models\Project;
use App\Models\Report;
use App\Models\ReporterInstallation;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project))->assertRedirect(route('login'));
});

it('creates the project connection and reveals its credential once', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Show::class, ['project' => $project])
        ->set('environment', 'local')
        ->call('createConnection')
        ->assertSet('showCredential', true)
        ->assertSee('ATREYU_KEY=atry_', false);

    $installation = ReporterInstallation::sole();
    expect($installation->name)->toBe($project->name)
        ->and($installation->created_by)->toBe($user->id)
        ->and($installation->getRawOriginal('secret'))->not->toBe($installation->secret)
        ->and($component->get('issuedCredential'))->toStartWith($installation->key_id.'.');

    $component->call('clearCredential')
        ->assertSet('issuedCredential', '')
        ->assertSet('showCredential', false);
});

it('clears the issued credential when the modal is dismissed', function () {
    $project = Project::factory()->create(['name' => "O'Brien & Co"]);

    Livewire::actingAs(User::factory()->create())
        ->test(Show::class, ['project' => $project])
        ->call('createConnection')
        ->assertSeeHtml('Install O&#039;Brien &amp; Co')
        ->set('showCredential', false)
        ->assertSet('issuedCredential', '');
});

it('rotates and revokes a project connection', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $installation = ReporterInstallation::factory()->for($project)->create();
    $originalSecret = $installation->secret;

    $component = Livewire::actingAs($user)->test(Show::class, ['project' => $project]);

    $component->call('rotateConnection')
        ->assertSet('showCredential', true);

    expect($installation->fresh()->secret)->not->toBe($originalSecret);

    $component->call('revokeConnection');
    expect($installation->fresh()->revoked_at)->not->toBeNull();
});

it('only manages the connection belonging to the current project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $connection = ReporterInstallation::factory()->for($project)->create();
    $otherConnection = ReporterInstallation::factory()->create();
    $otherSecret = $otherConnection->secret;

    Livewire::actingAs($user)
        ->test(Show::class, ['project' => $project])
        ->call('rotateConnection');

    expect($connection->fresh()->secret)->not->toBe($connection->secret)
        ->and($otherConnection->fresh()->secret)->toBe($otherSecret);
});

it('does not create a second connection for a project', function () {
    $project = Project::factory()->create();
    ReporterInstallation::factory()->for($project)->create();

    Livewire::actingAs(User::factory()->create())
        ->test(Show::class, ['project' => $project])
        ->call('createConnection')
        ->assertHasErrors(['environment']);

    expect($project->connection()->count())->toBe(1);
});

it('adds and removes a repository URL for the project', function () {
    $project = Project::factory()->create();

    $component = Livewire::actingAs(User::factory()->create())
        ->test(Show::class, ['project' => $project])
        ->set('repositoryUrl', 'https://github.com/advancedhouseplans/atreyu/')
        ->call('saveRepository')
        ->assertHasNoErrors()
        ->assertSee('advancedhouseplans/atreyu')
        ->assertDispatched('toast');

    expect($project->fresh()->repository_url)->toBe('https://github.com/advancedhouseplans/atreyu');

    $component->call('removeRepository')->assertDispatched('toast');

    expect($project->fresh()->repository_url)->toBeNull();
});

it('validates the repository URL', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(Show::class, ['project' => Project::factory()->create()])
        ->set('repositoryUrl', 'not-a-url')
        ->call('saveRepository')
        ->assertHasErrors(['repositoryUrl']);
});

it('separates project reports and tasks and filters tasks by assignee', function () {
    $project = Project::factory()->create(['slug' => 'clara']);
    $firstUser = User::factory()->create(['name' => 'Jamie']);
    $secondUser = User::factory()->create(['name' => 'Taylor']);
    Report::factory()->create(['source_slug' => 'clara', 'title' => 'Checkout report']);
    Task::factory()->for($project)->for($firstUser, 'assignee')->create(['title' => 'Jamie task']);
    Task::factory()->for($project)->for($secondUser, 'assignee')->create(['title' => 'Taylor task']);
    Task::factory()->create(['title' => 'Other project task']);

    Livewire::actingAs($firstUser)
        ->test(Show::class, ['project' => $project])
        ->assertSet('tab', 'reports')
        ->assertSee('Checkout report')
        ->assertSee('Reporter')
        ->assertSee('Received')
        ->assertDontSee('All assignees')
        ->assertDontSee('Jamie task')
        ->assertDontSee('Taylor task')
        ->assertDontSee('Other project task')
        ->set('tab', 'tasks')
        ->assertDontSee('Checkout report')
        ->assertSee('Assignee')
        ->assertSee('Status')
        ->assertSee('All assignees')
        ->assertSee('Jamie task')
        ->assertSee('Taylor task')
        ->set('taskAssignee', (string) $firstUser->id)
        ->assertSee('Jamie task')
        ->assertDontSee('Taylor task')
        ->assertDontSee('Other project task');
});
