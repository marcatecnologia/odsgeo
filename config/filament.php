<?php

return [
    'path' => 'admin',
    'domain' => null,
    'home_url' => '/',
    'auth' => [
        'guard' => 'web',
        'pages' => [
            'login' => \Filament\Pages\Auth\Login::class,
        ],
    ],
    'pages' => [
        'namespace' => 'App\\Filament\\Pages',
        'path' => app_path('Filament/Pages'),
        'register' => [],
    ],
    'resources' => [
        'namespace' => 'App\\Filament\\Resources',
        'path' => app_path('Filament/Resources'),
        'register' => [],
    ],
    'widgets' => [
        'namespace' => 'App\\Filament\\Widgets',
        'path' => app_path('Filament/Widgets'),
        'register' => [],
    ],
    'layouts' => [
        'app' => \Filament\Layouts\Layout::class,
        'auth' => \Filament\Layouts\Layout::class,
    ],
    'theme' => [
        'colors' => [
            'primary' => [
                50 => '238, 242, 255',
                100 => '224, 231, 255',
                200 => '199, 210, 254',
                300 => '165, 180, 252',
                400 => '129, 140, 248',
                500 => '99, 102, 241',
                600 => '79, 70, 229',
                700 => '67, 56, 202',
                800 => '55, 48, 163',
                900 => '49, 46, 129',
                950 => '30, 27, 75',
            ],
        ],
    ],
]; 