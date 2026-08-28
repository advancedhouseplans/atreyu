<?php

use App\Support\UserAgentParser;

it('parses a chrome UA', function () {
    $out = UserAgentParser::parse(
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    );
    expect($out['browser'])->toBe('Chrome')
        ->and($out['os'])->toBe('macOS');
});

it('returns nulls for empty', function () {
    expect(UserAgentParser::parse(''))->toBe([
        'browser' => null, 'browser_version' => null, 'os' => null, 'os_version' => null, 'device' => null,
    ]);
});
