<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestFrontendIntegration extends Command
{
    protected $signature = 'geoserver:test-frontend-integration';
    protected $description = 'Testa a integração entre o frontend e o GeoServer';

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
        $this->info('Testando integração entre frontend e GeoServer...');

        try {
            // 1. Testar WFS com CORS
            $this->testWFSWithCORS();

            // 2. Testar WMS com CORS
            $this->testWMSWithCORS();

            // 3. Testar GetFeature com filtro
            $this->testGetFeatureWithFilter();

            $this->info('Testes concluídos com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante os testes: ' . $e->getMessage());
            Log::error('Erro nos testes de integração', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function testWFSWithCORS()
    {
        $this->info('Testando WFS com CORS...');

        $response = Http::withHeaders([
            'Origin' => config('app.url'),
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Content-Type'
        ])->withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:municipios",
                'maxFeatures' => 1
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao testar WFS com CORS: ' . $response->body());
        }

        $this->info('WFS com CORS funcionando corretamente.');
    }

    protected function testWMSWithCORS()
    {
        $this->info('Testando WMS com CORS...');

        $response = Http::withHeaders([
            'Origin' => config('app.url'),
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Content-Type'
        ])->withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/wms", [
                'service' => 'WMS',
                'version' => '1.1.1',
                'request' => 'GetMap',
                'layers' => "{$this->workspace}:municipios",
                'bbox' => '-74.0,-33.8,-34.0,5.3',
                'width' => 768,
                'height' => 768,
                'srs' => 'EPSG:4326',
                'format' => 'image/png'
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao testar WMS com CORS: ' . $response->body());
        }

        $this->info('WMS com CORS funcionando corretamente.');
    }

    protected function testGetFeatureWithFilter()
    {
        $this->info('Testando GetFeature com filtro...');

        $filter = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ogc:Filter xmlns:ogc="http://www.opengis.net/ogc">
    <ogc:PropertyIsEqualTo>
        <ogc:PropertyName>uf</ogc:PropertyName>
        <ogc:Literal>SP</ogc:Literal>
    </ogc:PropertyIsEqualTo>
</ogc:Filter>
XML;

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:municipios",
                'filter' => $filter
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao testar GetFeature com filtro: ' . $response->body());
        }

        $this->info('GetFeature com filtro funcionando corretamente.');
    }
} 