<?php

use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('settings page offers a logout form at every breakpoint', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk();

    expect($response->getContent())
        ->toMatch('#<form[^>]+action="'.preg_quote(route('logout'), '#').'"[^>]*>\s*<input type="hidden" name="_token"#')
        ->toContain('data-test="settings-logout-button"');
});

test('settings nav keeps its active item after a livewire action', function () {
    $this->actingAs(User::factory()->create());

    // Livewire::test() reports a synthetic path, so replay a real update request from the rendered page's snapshot.
    $page = $this->get(route('profile.edit'))->assertOk()->getContent();
    preg_match_all('/wire:snapshot="([^"]+)"/', $page, $matches);
    $snapshot = collect($matches[1])
        ->map(fn (string $snapshot): string => html_entity_decode($snapshot))
        ->first(fn (string $snapshot): bool => data_get(json_decode($snapshot, true), 'memo.name') !== 'notifications.unread-indicator');

    $update = $this->postJson(route('default-livewire.update'), ['components' => [[
        'snapshot' => $snapshot,
        'updates' => [],
        'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]],
    ]]], ['X-Livewire' => 'true'])->assertOk();

    $html = $update->json('components.0.effects.html');

    expect($html)->toMatch('/href="[^"]*\/settings\/profile"\s+wire:navigate\s+aria-current="page"/')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});

test('delete account modal clears the typed password when it closes', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.delete-user-modal')
        ->set('showModal', true)
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password'])
        ->set('showModal', false)
        ->assertSet('password', '')
        ->assertHasNoErrors();
});
