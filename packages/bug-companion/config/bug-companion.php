<?php

return [
    'source_slug' => env('BUG_COMPANION_SOURCE', ''),
    'endpoint' => env('BUG_COMPANION_ENDPOINT', ''),
    'secret' => env('BUG_COMPANION_SECRET', ''),
    'enabled' => env('BUG_COMPANION_ENABLED', true),
    'context_ttl' => env('BUG_COMPANION_CTX_TTL', 3600),
    'max_queries' => 100,
    'max_models' => 200,
];
