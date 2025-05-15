<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do SIGEF
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para integração com o SIGEF
    |
    */

    /*
    |--------------------------------------------------------------------------
    | SIGEF WFS URL
    |--------------------------------------------------------------------------
    |
    | URL base do serviço WFS do SIGEF
    |
    */
    'wfs' => [
        'url' => env('SIGEF_WFS_URL', 'https://sigef.incra.gov.br/geoserver/wfs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de cache para as requisições do SIGEF
    |
    */
    'cache' => [
        'enabled' => env('SIGEF_CACHE_ENABLED', true),
        'driver' => env('SIGEF_CACHE_DRIVER', 'redis'),
        'ttl' => [
            'parcelas' => env('SIGEF_CACHE_TTL_PARCELAS', 300), // 5 minutos
            'municipios' => env('SIGEF_CACHE_TTL_MUNICIPIOS', 1296000), // 15 dias
            'coordenadas' => env('SIGEF_CACHE_TTL_COORDENADAS', 300), // 5 minutos
            'codigo' => env('SIGEF_CACHE_TTL_CODIGO', 300), // 5 minutos
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de paginação para as requisições do SIGEF
    |
    */
    'pagination' => [
        'enabled' => env('SIGEF_PAGINATION_ENABLED', true),
        'per_page' => env('SIGEF_PAGINATION_PER_PAGE', 50),
        'max_pages' => env('SIGEF_PAGINATION_MAX_PAGES', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttle Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de throttle para limitar o número de requisições
    |
    */
    'throttle' => [
        'enabled' => env('SIGEF_THROTTLE_ENABLED', true),
        'max_requests' => env('SIGEF_THROTTLE_MAX_REQUESTS', 60),
        'decay_seconds' => env('SIGEF_THROTTLE_DECAY_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de retry para requisições que falham
    |
    */
    'retry' => [
        'enabled' => env('SIGEF_RETRY_ENABLED', true),
        'max_attempts' => env('SIGEF_RETRY_MAX_ATTEMPTS', 3),
        'delay' => env('SIGEF_RETRY_DELAY', 1000), // milissegundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Geometry Settings
    |--------------------------------------------------------------------------
    |
    | Configurações para simplificação de geometrias
    |
    */
    'geometry' => [
        'simplify' => env('SIGEF_GEOMETRY_SIMPLIFY', true),
        'tolerance' => env('SIGEF_GEOMETRY_TOLERANCE', 0.0001),
        'max_points' => env('SIGEF_GEOMETRY_MAX_POINTS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Coordinate Search Settings
    |--------------------------------------------------------------------------
    |
    | Configurações para busca por coordenadas
    |
    */
    'coordenada' => [
        'raio_minimo' => env('SIGEF_COORDENADA_RAIO_MINIMO', 100), // metros
        'raio_maximo' => env('SIGEF_COORDENADA_RAIO_MAXIMO', 10000), // metros
        'raio_padrao' => env('SIGEF_COORDENADA_RAIO_PADRAO', 1000), // metros
    ],
]; 