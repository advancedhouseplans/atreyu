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
        ->assertDontSee('Preferences')
        ->assertSee('aria-current="page"', false)
        ->assertDontSee('drawer');

    // `\b` skips `primary-`; `mary-theme` is the legacy localStorage key theme-script clears.
    expect($response->getContent())->not->toMatch('/\bmary-(?!theme)/');

    expect(substr_count($response->getContent(), 'aria-label="Reports"'))->toBe(2);

    expect(substr_count($response->getContent(), '>Settings<'))->toBe(1);
});

test('dashboard route has been removed', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertNotFound();
});
