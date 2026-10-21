<?php

use App\Enums\Accent;
use App\Enums\Appearance;
use App\Models\User;
use Livewire\Livewire;

test('appearance and accent are saved and applied', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.appearance')
        ->call('selectAppearance', 'dark')->assertDispatched('ob-theme-apply', appearance: 'dark', accent: 'iris')
        ->call('selectAccent', 'amber')->assertDispatched('ob-theme-apply', appearance: 'dark', accent: 'amber');

    expect($user->refresh())->appearance->toBe(Appearance::Dark)->accent->toBe(Accent::Amber);
});

test('invalid values are rejected', function () {
    $user = User::factory()->create(['appearance' => Appearance::Light]);
    $this->actingAs($user);

    Livewire::test('pages::settings.appearance')
        ->call('selectAppearance', 'synthwave')->assertHasErrors(['appearance'])
        ->call('selectAccent', 'puce')->assertHasErrors(['accent']);

    expect($user->refresh()->appearance)->toBe(Appearance::Light);
});

test('the saved preference is on the html element before paint', function () {
    $user = User::factory()->create(['appearance' => Appearance::Light, 'accent' => Accent::Teal]);

    $this->actingAs($user)->get(route('appearance.edit'))->assertOk()
        ->assertSee('data-appearance="light" data-mode="light" data-accent="teal"', false)
        ->assertDontSee('data-theme=', false);
});

test('accent swatches are native radios with only the saved accent checked', function () {
    $user = User::factory()->create(['accent' => Accent::Teal]);

    $html = $this->actingAs($user)->get(route('appearance.edit'))->assertOk()->getContent();

    expect(preg_match_all('/type="radio"\s+name="accent"/', $html))->toBe(count(Accent::cases()))
        ->and(preg_match_all('/name="accent"[^>]*\schecked[\s>]/', $html))->toBe(1)
        ->and($html)->toMatch('/value="teal"[^>]*\schecked/');
});
