<?php

return [
    'enabled' => env('ATREYU_ENABLED', true),
    'url' => env('ATREYU_URL', env('BUG_COMPANION_ENDPOINT', '')),
    'key' => env('ATREYU_KEY', env('BUG_COMPANION_SECRET', '')),
    'project' => env('ATREYU_PROJECT', env('BUG_COMPANION_SOURCE', '')),
    'version' => env('ATREYU_APP_VERSION'),
    'context_ttl' => (int) env('ATREYU_CONTEXT_TTL', 600),
    'token_ttl' => (int) env('ATREYU_TOKEN_TTL', 900),
    'launcher' => (bool) env('ATREYU_LAUNCHER', true),
    'diagnostic_window' => (int) env('ATREYU_DIAGNOSTIC_WINDOW', 300),
    'diagnostic_requests' => (int) env('ATREYU_DIAGNOSTIC_REQUESTS', 5),
    'activation_ttl' => (int) env('ATREYU_ACTIVATION_TTL', 2),
    'activation_refresh' => (int) env('ATREYU_ACTIVATION_REFRESH', 60),

    'telemetry' => [
        'queries' => true,
        'query_bindings' => 'identifiers',
        'models' => true,
        'logs' => true,
        'user' => true,
        'exceptions' => true,
        'max_queries' => 100,
        'max_models' => 200,
        'max_logs' => 100,
        'max_exceptions' => 10,
        'max_string_length' => 500,
        'log_levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        'sensitive_keys' => [
            'authorization', 'cookie', 'password', 'password_confirmation', 'token',
            'secret', 'api_key', 'access_token', 'refresh_token', 'credit_card', 'ssn',
        ],
    ],
];
