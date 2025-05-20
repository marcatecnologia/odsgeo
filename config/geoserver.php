<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do GeoServer
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para integração com o GeoServer
    |
    */

    /*
    |--------------------------------------------------------------------------
    | GeoServer Configuration
    |--------------------------------------------------------------------------
    |
    | Aqui você pode configurar as opções de integração com o GeoServer.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | GeoServer URL
    |--------------------------------------------------------------------------
    |
    | URL base do GeoServer
    |
    */
    'url' => env('GEOSERVER_URL', 'http://localhost:8080/geoserver'),

    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    |
    | Nome do workspace no GeoServer
    |
    */
    'workspace' => env('GEOSERVER_WORKSPACE', 'odsgeo'),

    /*
    |--------------------------------------------------------------------------
    | Layer
    |--------------------------------------------------------------------------
    |
    | Nome da camada de parcelas SIGEF no GeoServer
    |
    */
    'parcelas_layer' => env('GEOSERVER_LAYER', 'parcelas_sigef_brasil'),

    /*
    |--------------------------------------------------------------------------
    | Credenciais
    |--------------------------------------------------------------------------
    |
    | Credenciais de acesso ao GeoServer
    |
    */
    'username' => env('GEOSERVER_USERNAME', 'admin'),
    'password' => env('GEOSERVER_PASSWORD', 'geoserver'),
    'store' => env('GEOSERVER_STORE', 'postgis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de cache para as consultas ao GeoServer
    |
    */
    'cache_enabled' => env('GEOSERVER_CACHE_ENABLED', true),
    'cache_time' => env('GEOSERVER_CACHE_TIME', 3600),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de timeout e retry para as requisições ao GeoServer
    |
    */
    'timeout' => env('GEOSERVER_TIMEOUT', 30),
    'retry_attempts' => env('GEOSERVER_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('GEOSERVER_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de paginação para as consultas ao GeoServer
    |
    */
    'pagination' => [
        'per_page' => env('GEOSERVER_PAGINATION_PER_PAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de CORS para o GeoServer
    |
    */
    'cors' => [
        'allowed_origins' => explode(',', env('GEOSERVER_CORS_ALLOWED_ORIGINS', 'http://odsgeo.test,https://odsgeo.test')),
        'allowed_methods' => explode(',', env('GEOSERVER_CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,HEAD,OPTIONS')),
        'allowed_headers' => explode(',', env('GEOSERVER_CORS_ALLOWED_HEADERS', '*')),
        'allow_credentials' => env('GEOSERVER_CORS_ALLOW_CREDENTIALS', true),
        'max_age' => env('GEOSERVER_CORS_MAX_AGE', 3600),
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
        'buffer_distance' => env('GEOSERVER_BUFFER_DISTANCE', 0.0001), // Aproximadamente 10 metros
        'simplify_tolerance' => env('GEOSERVER_SIMPLIFY_TOLERANCE', 0.00001), // Aproximadamente 1 metro
    ],

    /*
    |--------------------------------------------------------------------------
    | Coordinate Settings
    |--------------------------------------------------------------------------
    |
    | Configurações para busca por coordenadas
    |
    */
    'coordinate' => [
        'raio_minimo' => env('SIGEF_COORDENADA_RAIO_MINIMO', 100),
        'raio_maximo' => env('SIGEF_COORDENADA_RAIO_MAXIMO', 10000),
        'raio_padrao' => env('SIGEF_COORDENADA_RAIO_PADRAO', 1000),
        'raio_incremento' => env('SIGEF_COORDENADA_RAIO_INCREMENTO', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de performance para as consultas ao GeoServer
    |
    */
    'performance' => [
        'max_features' => env('GEOSERVER_MAX_FEATURES', 1000),
        'timeout' => env('GEOSERVER_TIMEOUT', 30),
        'cache_ttl' => env('GEOSERVER_CACHE_TTL', 3600), // 1 hora
    ],

    /*
    |--------------------------------------------------------------------------
    | Layers Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações das camadas do GeoServer.
    |
    */
    'layers' => [
        'parcelas' => [
            'name' => 'parcelas_sigef_brasil',
            'srs' => 'EPSG:4326',
            'geometry_field' => 'geom',
            'geometry_type' => 'Polygon',
        ],
        'municipios' => [
            'name' => 'br_municipios_2024',
            'srs' => 'EPSG:4326',
            'geometry_field' => 'geom',
            'geometry_type' => 'Polygon',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Coordinate Search Settings
    |--------------------------------------------------------------------------
    |
    | Configurações para busca por coordenadas.
    |
    */
    'coordenada' => [
        'raio_padrao' => env('GEOSERVER_RAIO_PADRAO', 1000), // 1 km
        'raio_minimo' => env('GEOSERVER_RAIO_MINIMO', 1), // 1 metro
        'raio_maximo' => env('GEOSERVER_RAIO_MAXIMO', 10000), // 10 km
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Configurações para as buscas.
    |
    */
    'search' => [
        'nome_min_length' => env('GEOSERVER_NOME_MIN_LENGTH', 3),
        'nome_max_length' => env('GEOSERVER_NOME_MAX_LENGTH', 255),
        'codigo_length' => env('GEOSERVER_CODIGO_LENGTH', 13),
        'ccir_length' => env('GEOSERVER_CCIR_LENGTH', 14),
        'cnpj_length' => env('GEOSERVER_CNPJ_LENGTH', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Municipios Layer
    |--------------------------------------------------------------------------
    |
    | Nome da camada de municipios no GeoServer
    |
    */
    'municipios_layer' => env('GEOSERVER_MUNICIPIOS_LAYER', 'br_municipios_2024'),
    'estados_layer' => env('GEOSERVER_ESTADOS_LAYER', 'br_uf_2024'),
]; 