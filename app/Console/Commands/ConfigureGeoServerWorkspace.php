<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigureGeoServerWorkspace extends Command
{
    protected $signature = 'geoserver:configure-workspace';
    protected $description = 'Configura o workspace e o store no GeoServer';

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
        $this->info('Configurando workspace e store no GeoServer...');

        try {
            // 1. Criar workspace
            $this->createWorkspace();

            // 2. Criar store
            $this->createStore();

            $this->info('Workspace e store configurados com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao configurar workspace e store: ' . $e->getMessage());
            Log::error('Erro ao configurar workspace e store no GeoServer', [
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

        $workspaceConfig = [
            'workspace' => [
                'name' => $this->workspace,
                'isolated' => false
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/workspaces", $workspaceConfig);

        if (!$response->successful() && $response->status() !== 401) {
            throw new \Exception('Erro ao criar workspace: ' . $response->body());
        }

        $this->info('Workspace criado com sucesso.');
    }

    protected function createStore()
    {
        $this->info('Criando store...');

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
                    'dbtype' => 'postgis',
                    'Expose primary keys' => 'true',
                    'validate connections' => 'true',
                    'max connections' => '10',
                    'min connections' => '1',
                    'fetch size' => '1000',
                    'preparedStatements' => 'true'
                ]
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/workspaces/{$this->workspace}/datastores", $storeConfig);

        if (!$response->successful() && $response->status() !== 401) {
            throw new \Exception('Erro ao criar store: ' . $response->body());
        }

        $this->info('Store criado com sucesso.');
    }
} 