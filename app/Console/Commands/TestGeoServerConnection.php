<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGeoServerConnection extends Command
{
    protected $signature = 'geoserver:test-connection';
    protected $description = 'Testa a conexão com o GeoServer';

    protected $baseUrl;
    protected $username;
    protected $password;
    protected $workspace;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('geoserver.url');
        $this->username = config('geoserver.username');
        $this->password = config('geoserver.password');
        $this->workspace = config('geoserver.workspace');
    }

    public function handle()
    {
        $this->info('Testando conexão com o GeoServer...');

        try {
            // 1. Testar conexão básica
            $this->testBasicConnection();

            // 2. Testar workspace
            $this->testWorkspace();

            // 3. Testar WFS
            $this->testWFS();

            $this->info('Testes concluídos com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante os testes: ' . $e->getMessage());
            Log::error('Erro nos testes do GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function testBasicConnection()
    {
        $this->info('Testando conexão básica...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/about/version");

        if ($response->successful()) {
            $version = $response->json()['about']['resource'][0]['version'];
            $this->info("Conexão bem sucedida! Versão do GeoServer: {$version}");
        } else {
            throw new \Exception('Erro ao conectar com o GeoServer: ' . $response->body());
        }
    }

    protected function testWorkspace()
    {
        $this->info('Testando workspace...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/workspaces/{$this->workspace}");

        if ($response->successful()) {
            $this->info('Workspace encontrado e acessível.');
        } else {
            throw new \Exception('Erro ao acessar workspace: ' . $response->body());
        }
    }

    protected function testWFS()
    {
        $this->info('Testando WFS...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetCapabilities'
            ]);

        if ($response->successful()) {
            $this->info('WFS está funcionando corretamente.');
        } else {
            throw new \Exception('Erro ao testar WFS: ' . $response->body());
        }
    }
} 