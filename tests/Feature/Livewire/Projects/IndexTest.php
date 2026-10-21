<?php

use App\Livewire\Projects\Index;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('creates a project with a unique slug', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(Index::class)
        ->set('name', 'Internal Work')
        ->set('description', 'Everything that supports the team.')
        ->call('createProject')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'name' => 'Internal Work',
        'slug' => 'internal-work',
    ]);

    $project = Project::where('slug', 'internal-work')->sole();
    $component->assertRedirect(route('projects.show', ['project' => $project, 'connect' => 1]));
});

it('requires authentication through the route', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
});
