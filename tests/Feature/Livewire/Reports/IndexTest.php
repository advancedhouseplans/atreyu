<?php

use App\Livewire\Reports\Index;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders the list for logged-in users', function () {
    $u = User::factory()->create();
    Report::factory()->count(3)->create();
    Report::factory()->create(['archived_at' => now(), 'title' => 'shipped']);

    Livewire::actingAs($u)->test(Index::class)
        ->assertOk()
        ->assertDontSee('Matching reports')
        ->assertSet('view', 'inbox')
        ->assertDontSee('shipped')
        ->set('view', 'archived')
        ->assertSee('shipped');
});

it('filters triaged reports by their linked task', function () {
    $user = User::factory()->create();
    $converted = Report::factory()->create(['title' => 'Converted report']);
    $waiting = Report::factory()->create(['title' => 'Waiting report']);
    Task::factory()->for($converted)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('view', 'triaged')
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

it('shows repeated reports as one issue with an occurrence count', function () {
    $user = User::factory()->create();
    $canonical = Report::factory()->create(['title' => 'Checkout failed']);
    Report::factory()->create([
        'occurrence_parent_id' => $canonical->id,
        'title' => 'Checkout failed',
    ]);

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee('Checkout failed')
        ->assertSee('2 occurrences');
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

it('renders the calendar year from january first with report frequency levels', function () {
    $user = User::factory()->create();

    foreach ([1 => 1, 2 => 2, 3 => 4, 4 => 8] as $daysAgo => $count) {
        Report::factory()->count($count)->create(['created_at' => today()->subDays($daysAgo)->addHours(12)]);
    }

    $html = Livewire::actingAs($user)->test(Index::class)->html();

    expect(substr_count($html, 'data-report-day='))->toBe(365)
        ->and($html)->toMatch('/data-report-day="'.today()->startOfYear()->toDateString().'"\s+data-level="0"/')
        ->and($html)->toMatch('/data-report-day="'.today()->subDay()->toDateString().'"\s+data-level="1"/')
        ->and($html)->toMatch('/data-report-day="'.today()->subDays(2)->toDateString().'"\s+data-level="2"/')
        ->and($html)->toMatch('/data-report-day="'.today()->subDays(3)->toDateString().'"\s+data-level="3"/')
        ->and($html)->toMatch('/data-report-day="'.today()->subDays(4)->toDateString().'"\s+data-level="4"/');
});
