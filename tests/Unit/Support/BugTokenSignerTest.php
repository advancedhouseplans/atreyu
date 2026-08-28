<?php

use App\Support\BugTokenSigner;

beforeEach(function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'x', 'secret' => 'topsecret']);
});

function makeToken(array $payload, string $secret): string
{
    $b64 = fn ($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
    $body = $b64(json_encode($payload));
    $sig = $b64(hash_hmac('sha256', $body, $secret, true));

    return "{$body}.{$sig}";
}

it('accepts a valid token', function () {
    $token = makeToken(['source_slug' => 'ahp', 'env' => 'prod', 'endpoint' => 'x',
        'user_id' => 1, 'user_email' => 'a@b', 'user_name' => 'A', 'exp' => time() + 60], 'topsecret');
    expect(BugTokenSigner::verify($token))->toMatchArray(['source_slug' => 'ahp', 'user_id' => 1]);
});

it('rejects tampered signature', function () {
    $token = makeToken(['source_slug' => 'ahp', 'exp' => time() + 60], 'topsecret').'x';
    expect(BugTokenSigner::verify($token))->toBeNull();
});

it('rejects expired token', function () {
    $token = makeToken(['source_slug' => 'ahp', 'env' => 'p', 'endpoint' => 'x',
        'user_id' => 1, 'user_email' => 'a', 'user_name' => 'a', 'exp' => time() - 1], 'topsecret');
    expect(BugTokenSigner::verify($token))->toBeNull();
});

it('rejects unknown source slug', function () {
    $token = makeToken(['source_slug' => 'ghost', 'exp' => time() + 60], 'topsecret');
    expect(BugTokenSigner::verify($token))->toBeNull();
});
