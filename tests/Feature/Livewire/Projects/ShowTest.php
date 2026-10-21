<?php

use App\Livewire\Projects\Show;
use App\Models\Project;
use App\Models\ReporterInstallation;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project))->assertRedirect(route('login'));
});

it('creates an installation and reveals its credential once', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Show::class, ['project' => $project])
        ->set('name', 'Clara local')
        ->set('environment', 'local')
        ->call('createInstallation')
        ->assertSet('showCredential', true)
        ->assertSee('ATREYU_KEY=atry_', false);

    $installation = ReporterInstallation::sole();
    expect($installation->name)->toBe('Clara local')
        ->and($installation->created_by)->toBe($user->id)
        ->and($installation->getRawOriginal('secret'))->not->toBe($installation->secret)
        ->and($component->get('issuedCredential'))->toStartWith($installation->key_id.'.');

    $component->call('clearCredential')
        ->assertSet('issuedCredential', '')
        ->assertSet('showCredential', false);
});

it('rotates and revokes a project installation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $installation = ReporterInstallation::factory()->for($project)->create();
    $originalSecret = $installation->secret;

    $component = Livewire::actingAs($user)->test(Show::class, ['project' => $project]);

    $component->call('rotateInstallation', $installation->id)
        ->assertSet('showCredential', true);

    expect($installation->fresh()->secret)->not->toBe($originalSecret);

    $component->call('revokeInstallation', $installation->id);
    expect($installation->fresh()->revoked_at)->not->toBeNull();
});

it('cannot manage an installation from another project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $otherInstallation = ReporterInstallation::factory()->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['project' => $project])
        ->call('revokeInstallation', $otherInstallation->id)
        ->assertNotFound();
});
