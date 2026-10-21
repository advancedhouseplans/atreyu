<?php

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
                'theme' => 'light',
            ],
        ]);
});
