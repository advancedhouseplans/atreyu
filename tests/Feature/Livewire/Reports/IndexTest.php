<?php

use App\Livewire\Reports\Index;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders the list for logged-in users', function () {
    $u = User::factory()->create();
    Report::factory()->count(3)->create(['status' => 'new']);
    Report::factory()->create(['status' => 'done', 'title' => 'shipped']);

    Livewire::actingAs($u)->test(Index::class)
        ->assertOk()
        ->assertDontSee('Matching reports')
        ->assertSet('status', 'new')
        ->assertDontSee('shipped')
        ->set('status', 'done')
        ->assertSee('shipped');
});

it('filters converted reports by their linked task', function () {
    $user = User::factory()->create();
    $converted = Report::factory()->create(['status' => 'done', 'title' => 'Converted report']);
    $waiting = Report::factory()->create(['status' => 'new', 'title' => 'Waiting report']);
    Task::factory()->for($converted)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('status', 'converted')
        ->assertSee('Converted report')
        ->assertDontSee('Waiting report');
});

it('builds its source filter from incoming reports', function () {
    $user = User::factory()->create();
    Report::factory()->create(['source_slug' => 'clara-desktop']);

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee('Clara Desktop')
        ->set('source', 'clara-desktop')
        ->assertOk();
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

it('falls back to safe sort when given a bogus URL param', function () {
    $u = User::factory()->create();
    Report::factory()->count(3)->create();

    // Bogus sortBy shouldn't 500 the page.
    Livewire::actingAs($u)->test(Index::class)
        ->set('sortBy', 'DROP TABLE users')
        ->set('sortDir', 'sideways')
        ->assertOk();
});
