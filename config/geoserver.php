<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GeoServer Configuration
    |--------------------------------------------------------------------------
    |
    | Aqui você pode configurar as conexões com o GeoServer.
    |
    */

    'url' => env('GEOSERVER_URL', 'http://localhost:8080/geoserver'),
    
    'workspace' => env('GEOSERVER_WORKSPACE', 'odsgeo'),
    
    'username' => env('GEOSERVER_USERNAME', 'admin'),
    
    'password' => env('GEOSERVER_PASSWORD', 'geoserver'),
    
    'store' => env('GEOSERVER_STORE', 'postgis'),
    
    'timeout' => env('GEOSERVER_TIMEOUT', 30),
    
    'retry_attempts' => env('GEOSERVER_RETRY_ATTEMPTS', 3),
    
    'retry_delay' => env('GEOSERVER_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de CORS para o GeoServer.
    |
    */
    'cors' => [
        'allowed_origins' => explode(',', env('GEOSERVER_CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => explode(',', env('GEOSERVER_CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', env('GEOSERVER_CORS_ALLOWED_HEADERS', 'Content-Type,Authorization')),
        'max_age' => env('GEOSERVER_CORS_MAX_AGE', 3600),
    ],
]; 