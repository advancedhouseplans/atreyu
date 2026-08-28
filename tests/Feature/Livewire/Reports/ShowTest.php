<?php

use App\Livewire\Reports\Show;
use App\Models\Report;
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
