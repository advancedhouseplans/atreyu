<?php

use App\Enums\Theme;
use App\Models\User;
use Livewire\Livewire;

test('appearance settings offer eight light and eight dark daisyui themes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertOk();

    foreach (Theme::cases() as $theme) {
        $response
            ->assertSee($theme->label())
            ->assertSee('data-theme="'.$theme->value.'"', false);
    }

    expect(Theme::lightThemes())->toHaveCount(8)
        ->and(Theme::darkThemes())->toHaveCount(8)
        ->and(Theme::values())->not->toContain('atreyu')
        ->and(Theme::values())->not->toContain('atreyu-dark');
});

test('a selected theme is saved to the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.appearance')
        ->call('selectTheme', Theme::Night->value)
        ->assertSet('theme', Theme::Night->value)
        ->assertHasNoErrors()
        ->assertDispatched('atreyu-theme-changed', theme: Theme::Night->value);

    expect($user->refresh()->theme)->toBe(Theme::Night);
});

test('an invalid theme cannot be saved', function () {
    $user = User::factory()->create(['theme' => Theme::Black]);

    $this->actingAs($user);

    Livewire::test('pages::settings.appearance')
        ->call('selectTheme', 'made-up-theme')
        ->assertHasErrors(['theme']);

    expect($user->refresh()->theme)->toBe(Theme::Black);
});

test('the saved user theme is applied before the page renders', function () {
    $user = User::factory()->create(['theme' => Theme::Synthwave]);

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('<html lang="en" data-theme="synthwave">', false)
        ->assertSee("const savedTheme = 'synthwave'", false)
        ->assertSee('atreyu-theme')
        ->assertDontSee('mary-class');
});
