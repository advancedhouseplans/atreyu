<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'x', 'secret' => 'topsecret']);
    Route::post('/_test/token-check', function (Request $r) {
        return response()->json(['payload' => $r->attributes->get('bug.token')]);
    })->middleware('bug.token');
});

function tokenFor(array $payload, string $secret): string
{
    $b64 = fn ($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
    $body = $b64(json_encode($payload));
    $sig = $b64(hash_hmac('sha256', $body, $secret, true));

    return "{$body}.{$sig}";
}

it('rejects missing bearer', function () {
    $this->postJson('/_test/token-check')->assertStatus(401);
});

it('rejects bad signature', function () {
    $this->withHeader('Authorization', 'Bearer abc.def')
        ->postJson('/_test/token-check')->assertStatus(401);
});

it('accepts valid token and attaches payload', function () {
    $t = tokenFor(['source_slug' => 'ahp', 'env' => 'prod', 'endpoint' => 'x',
        'user_id' => 1, 'user_email' => 'a@b', 'user_name' => 'A', 'exp' => time() + 60], 'topsecret');
    $this->withHeader('Authorization', "Bearer {$t}")
        ->postJson('/_test/token-check')
        ->assertOk()
        ->assertJsonPath('payload.source_slug', 'ahp');
});
