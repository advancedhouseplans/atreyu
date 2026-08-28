<?php

return [
    'ahp' => [
        'name' => 'AHP',
        'endpoint' => env('BUGS_SOURCE_AHP_ENDPOINT', ''),
        'secret' => env('BUGS_SOURCE_AHP_SECRET', ''),
    ],
    'takeoff' => [
        'name' => 'Takeoff',
        'endpoint' => env('BUGS_SOURCE_TAKEOFF_ENDPOINT', ''),
        'secret' => env('BUGS_SOURCE_TAKEOFF_SECRET', ''),
    ],
];
