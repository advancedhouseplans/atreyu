<?php

use App\Livewire\Projects\Index;
use App\Models\User;
use Livewire\Livewire;

it('creates a project with a unique slug', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('name', 'Internal Work')
        ->set('description', 'Everything that supports the team.')
        ->call('createProject')
        ->assertHasNoErrors()
        ->assertSee('Internal Work');

    $this->assertDatabaseHas('projects', [
        'name' => 'Internal Work',
        'slug' => 'internal-work',
    ]);
});

it('requires authentication through the route', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
});
