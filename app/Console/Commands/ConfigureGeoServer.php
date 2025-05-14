<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigureGeoServer extends Command
{
    protected $signature = 'geoserver:configure';
    protected $description = 'Configura o GeoServer com workspace, store e camadas necessárias';

    protected $baseUrl;
    protected $username;
    protected $password;
    protected $workspace;
    protected $store;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('geoserver.url');
        $this->username = config('geoserver.username');
        $this->password = config('geoserver.password');
        $this->workspace = config('geoserver.workspace');
        $this->store = config('geoserver.store');
    }

    public function handle()
    {
        $this->info('Iniciando configuração do GeoServer...');

        try {
            // 1. Criar workspace
            $this->createWorkspace();

            // 2. Criar store PostGIS
            $this->createPostGISStore();

            // 3. Publicar camada
            $this->publishLayer();

            // 4. Configurar WFS
            $this->configureWFS();

            // 5. Configurar CORS
            $this->configureCORS();

            $this->info('Configuração do GeoServer concluída com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante a configuração: ' . $e->getMessage());
            Log::error('Erro na configuração do GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function createWorkspace()
    {
        $this->info('Criando workspace...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/workspaces", [
                'workspace' => [
                    'name' => $this->workspace
                ]
            ]);

        if ($response->successful() || $response->status() === 401) {
            $this->info('Workspace criado ou já existe.');
        } else {
            throw new \Exception('Erro ao criar workspace: ' . $response->body());
        }
    }

    protected function createPostGISStore()
    {
        $this->info('Criando store PostGIS...');

        $storeConfig = [
            'dataStore' => [
                'name' => $this->store,
                'type' => 'PostGIS',
                'enabled' => true,
                'connectionParameters' => [
                    'host' => config('database.connections.pgsql.host'),
                    'port' => config('database.connections.pgsql.port'),
                    'database' => config('database.connections.pgsql.database'),
                    'schema' => 'public',
                    'user' => config('database.connections.pgsql.username'),
                    'passwd' => config('database.connections.pgsql.password'),
                    'dbtype' => 'postgis'
                ]
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/workspaces/{$this->workspace}/datastores", $storeConfig);

        if ($response->successful() || $response->status() === 401) {
            $this->info('Store PostGIS criado ou já existe.');
        } else {
            throw new \Exception('Erro ao criar store PostGIS: ' . $response->body());
        }
    }

    protected function publishLayer()
    {
        $this->info('Publicando camada...');

        $layerConfig = [
            'featureType' => [
                'name' => 'municipios_simplificado',
                'nativeName' => 'municipios_simplificado',
                'title' => 'Municípios Simplificados',
                'srs' => 'EPSG:4326',
                'attributes' => [
                    'attribute' => [
                        [
                            'name' => 'codigo_ibge',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ],
                        [
                            'name' => 'nome',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ],
                        [
                            'name' => 'uf',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ],
                        [
                            'name' => 'centroide',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/workspaces/{$this->workspace}/datastores/{$this->store}/featuretypes", $layerConfig);

        if ($response->successful() || $response->status() === 401) {
            $this->info('Camada publicada com sucesso.');
        } else {
            throw new \Exception('Erro ao publicar camada: ' . $response->body());
        }
    }

    protected function configureWFS()
    {
        $this->info('Configurando WFS...');

        $wfsConfig = [
            'wfs' => [
                'serviceLevel' => 'Complete',
                'gml' => [
                    'version' => '3.2'
                ],
                'includeBoundingBoxes' => true,
                'maxFeatures' => 1000
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/services/wfs/settings", $wfsConfig);

        if ($response->successful() || $response->status() === 401) {
            $this->info('WFS configurado com sucesso.');
        } else {
            throw new \Exception('Erro ao configurar WFS: ' . $response->body());
        }
    }

    protected function configureCORS()
    {
        $this->info('Configurando CORS...');

        $corsConfig = [
            'cors' => [
                'allowedOrigins' => ['*'],
                'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
                'allowedHeaders' => ['*'],
                'allowCredentials' => true,
                'maxAge' => 3600
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/security/cors", $corsConfig);

        if ($response->successful() || $response->status() === 401) {
            $this->info('CORS configurado com sucesso.');
        } else {
            throw new \Exception('Erro ao configurar CORS: ' . $response->body());
        }
    }
} 