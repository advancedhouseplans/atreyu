<?php

use App\Livewire\Reports\Index;
use App\Models\Report;
use App\Models\User;
use Livewire\Livewire;

it('renders the list for logged-in users', function () {
    $u = User::factory()->create();
    Report::factory()->count(3)->create(['status' => 'new']);
    Report::factory()->create(['status' => 'done', 'title' => 'shipped']);

    Livewire::actingAs($u)->test(Index::class)
        ->assertOk()
        ->assertSee('shipped')
        ->set('status', 'new')
        ->assertDontSee('shipped');
});

it('searches by title', function () {
    $u = User::factory()->create();
    Report::factory()->create(['title' => 'network failure']);
    Report::factory()->create(['title' => 'button color']);

    Livewire::actingAs($u)->test(Index::class)
        ->set('q', 'network')
        ->assertSee('network failure')
        ->assertDontSee('button color');
});

it('requires auth via the route', function () {
    $this->get('/reports')->assertRedirect('/login');
});
