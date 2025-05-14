<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestMunicipiosLayer extends Command
{
    protected $signature = 'geoserver:test-municipios-layer';
    protected $description = 'Testa a camada de municípios no GeoServer';

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
        $this->info('Testando camada de municípios no GeoServer...');

        try {
            // 1. Testar GetCapabilities
            $this->testGetCapabilities();

            // 2. Testar GetFeature
            $this->testGetFeature();

            // 3. Testar GetFeatureInfo
            $this->testGetFeatureInfo();

            $this->info('Testes concluídos com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante os testes: ' . $e->getMessage());
            Log::error('Erro nos testes da camada de municípios', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function testGetCapabilities()
    {
        $this->info('Testando GetCapabilities...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetCapabilities'
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao testar GetCapabilities: ' . $response->body());
        }

        $this->info('GetCapabilities funcionando corretamente.');
    }

    protected function testGetFeature()
    {
        $this->info('Testando GetFeature...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:municipios",
                'maxFeatures' => 1
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao testar GetFeature: ' . $response->body());
        }

        $this->info('GetFeature funcionando corretamente.');
    }

    protected function testGetFeatureInfo()
    {
        $this->info('Testando GetFeatureInfo...');

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/wms", [
                'service' => 'WMS',
                'version' => '1.1.1',
                'request' => 'GetFeatureInfo',
                'layers' => "{$this->workspace}:municipios",
                'query_layers' => "{$this->workspace}:municipios",
                'bbox' => '-74.0,-33.8,-34.0,5.3',
                'feature_count' => 1,
                'height' => 101,
                'width' => 101,
                'format' => 'image/png',
                'info_format' => 'application/json',
                'x' => 50,
                'y' => 50,
                'srs' => 'EPSG:4326'
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao testar GetFeatureInfo: ' . $response->body());
        }

        $this->info('GetFeatureInfo funcionando corretamente.');
    }
} 