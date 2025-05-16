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
    | URL do serviço WFS do SIGEF para consulta de parcelas
    |
    */
    'wfs_url' => env('SIGEF_WFS_URL', 'https://sigef.incra.gov.br/geoserver/wfs'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de cache para as consultas ao SIGEF
    |
    */
    'cache_enabled' => env('SIGEF_CACHE_ENABLED', true),
    'cache_time' => env('SIGEF_CACHE_TIME', 3600), // 1 hora em segundos

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de timeout e retry para as requisições ao SIGEF
    |
    */
    'timeout' => env('SIGEF_TIMEOUT', 30),
    'connect_timeout' => env('SIGEF_CONNECT_TIMEOUT', 10),
    'max_retries' => env('SIGEF_MAX_RETRIES', 3),
    'retry_delay' => env('SIGEF_RETRY_DELAY', 1000), // 1 segundo em milissegundos

    /*
    |--------------------------------------------------------------------------
    | SSL Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de SSL para as requisições ao SIGEF
    |
    */
    'verify_ssl' => env('SIGEF_VERIFY_SSL', false),
    'ssl_version' => env('SIGEF_SSL_VERSION', 'TLSv1.2'),

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
        'load_more' => env('SIGEF_PAGINATION_LOAD_MORE', true),
        'infinite_scroll' => env('SIGEF_PAGINATION_INFINITE_SCROLL', false),
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
        'raio_incremento' => env('SIGEF_COORDENADA_RAIO_INCREMENTO', 500), // metros
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de performance para otimização
    |
    */
    'performance' => [
        'chunk_size' => env('SIGEF_PERFORMANCE_CHUNK_SIZE', 1000),
        'max_concurrent_requests' => env('SIGEF_PERFORMANCE_MAX_CONCURRENT_REQUESTS', 5),
        'request_timeout' => env('SIGEF_PERFORMANCE_REQUEST_TIMEOUT', 30), // segundos
        'memory_limit' => env('SIGEF_PERFORMANCE_MEMORY_LIMIT', '256M'),
    ],
]; 