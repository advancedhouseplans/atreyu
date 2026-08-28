<?php

it('exposes source config keyed by slug', function () {
    config()->set('bug-sources.ahp', ['name' => 'AHP', 'endpoint' => 'https://ahp.test', 'secret' => 's']);
    expect(config('bug-sources.ahp.name'))->toBe('AHP');
});

it('reads secrets from env in the shipped config', function () {
    $config = require config_path('bug-sources.php');
    expect($config)->toHaveKeys(['ahp', 'takeoff']);
    expect($config['ahp'])->toHaveKeys(['name', 'endpoint', 'secret']);
});
