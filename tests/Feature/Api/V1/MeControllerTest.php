<?php

use App\Enums\Appearance;
use App\Models\User;

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('returns the user for a valid personal access token', function () {
    $user = User::factory()->create([
        'name' => 'Bastian Balthazar Bux',
        'email' => 'bastian@atreyu.test',
    ]);
    $token = $user->createToken('macOS')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => 'Bastian Balthazar Bux',
                'email' => 'bastian@atreyu.test',
                'appearance' => 'system',
                'accent' => 'iris',
                'theme' => 'light',
            ],
        ]);
});

it('reports the dark theme for dark appearance users', function () {
    $user = User::factory()->create(['appearance' => Appearance::Dark]);

    $this->withToken($user->createToken('macOS')->plainTextToken)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.appearance', 'dark')
        ->assertJsonPath('data.theme', 'dark');
});
