<?php

use App\Models\User;

test('sidebar keeps only the primary work areas in priority order', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('reports.index'));

    $response->assertOk()
        ->assertSeeInOrder([
            'aria-label="Reports"',
            'aria-label="Projects"',
            'aria-label="Tasks"',
        ], escape: false)
        ->assertDontSee('Dashboard')
        ->assertDontSee('Switch theme')
        ->assertDontSee('Workspace')
        ->assertDontSee('Preferences');

    expect(substr_count($response->getContent(), '>Settings<'))->toBe(1);
});

test('dashboard route has been removed', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertNotFound();
});
