<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Throttle Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de throttle para limitar requisições
    |
    */

    'sigef' => [
        'enabled' => env('SIGEF_THROTTLE_ENABLED', true),
        'max_requests' => env('SIGEF_THROTTLE_MAX_REQUESTS', 60),
        'decay_seconds' => env('SIGEF_THROTTLE_DECAY_SECONDS', 60),
        'retry' => [
            'enabled' => env('SIGEF_RETRY_ENABLED', true),
            'max_attempts' => env('SIGEF_RETRY_MAX_ATTEMPTS', 3),
            'delay' => env('SIGEF_RETRY_DELAY', 1000),
        ],
    ],
]; 