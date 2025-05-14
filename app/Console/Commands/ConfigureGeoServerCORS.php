<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigureGeoServerCORS extends Command
{
    protected $signature = 'geoserver:configure-cors';
    protected $description = 'Configura o CORS no GeoServer';

    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('geoserver.url');
        $this->username = config('geoserver.username');
        $this->password = config('geoserver.password');
    }

    public function handle()
    {
        $this->info('Configurando CORS no GeoServer...');

        try {
            // 1. Configurar web.xml
            $this->configureWebXml();

            // 2. Configurar web.xml do GeoServer
            $this->configureGeoServerWebXml();

            $this->info('CORS configurado com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao configurar CORS: ' . $e->getMessage());
            Log::error('Erro ao configurar CORS no GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function configureWebXml()
    {
        $this->info('Configurando web.xml...');

        $corsFilter = [
            'filter' => [
                'filter-name' => 'CorsFilter',
                'filter-class' => 'org.apache.catalina.filters.CorsFilter',
                'init-param' => [
                    'cors.allowed.origins' => '*',
                    'cors.allowed.methods' => 'GET,POST,PUT,DELETE,HEAD,OPTIONS',
                    'cors.allowed.headers' => '*',
                    'cors.exposed.headers' => 'Content-Length,Content-Range',
                    'cors.support.credentials' => 'true',
                    'cors.preflight.maxage' => '1800'
                ]
            ],
            'filter-mapping' => [
                'filter-name' => 'CorsFilter',
                'url-pattern' => '/*'
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/security/config", $corsFilter);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar web.xml: ' . $response->body());
        }

        $this->info('web.xml configurado com sucesso.');
    }

    protected function configureGeoServerWebXml()
    {
        $this->info('Configurando web.xml do GeoServer...');

        $corsFilter = [
            'filter' => [
                'filter-name' => 'CorsFilter',
                'filter-class' => 'org.apache.catalina.filters.CorsFilter',
                'init-param' => [
                    'cors.allowed.origins' => '*',
                    'cors.allowed.methods' => 'GET,POST,PUT,DELETE,HEAD,OPTIONS',
                    'cors.allowed.headers' => '*',
                    'cors.exposed.headers' => 'Content-Length,Content-Range',
                    'cors.support.credentials' => 'true',
                    'cors.preflight.maxage' => '1800'
                ]
            ],
            'filter-mapping' => [
                'filter-name' => 'CorsFilter',
                'url-pattern' => '/geoserver/*'
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/security/config/geoserver", $corsFilter);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar web.xml do GeoServer: ' . $response->body());
        }

        $this->info('web.xml do GeoServer configurado com sucesso.');
    }
} 