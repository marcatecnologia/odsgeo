<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGeoServerPerformance extends Command
{
    protected $signature = 'geoserver:test-performance';
    protected $description = 'Testa a performance do GeoServer';

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
        $this->info('Testando performance do GeoServer...');

        try {
            // 1. Testar GetFeature com diferentes números de features
            $this->testGetFeaturePerformance();

            // 2. Testar GetMap com diferentes tamanhos
            $this->testGetMapPerformance();

            // 3. Testar GetFeatureInfo com diferentes níveis de zoom
            $this->testGetFeatureInfoPerformance();

            $this->info('Testes de performance concluídos com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante os testes de performance: ' . $e->getMessage());
            Log::error('Erro nos testes de performance do GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function testGetFeaturePerformance()
    {
        $this->info('Testando performance do GetFeature...');

        $featureCounts = [1, 10, 100, 1000];
        $results = [];

        foreach ($featureCounts as $count) {
            $startTime = microtime(true);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                    'service' => 'WFS',
                    'version' => '2.0.0',
                    'request' => 'GetFeature',
                    'typeName' => "{$this->workspace}:municipios",
                    'maxFeatures' => $count
                ]);

            if (!$response->successful()) {
                throw new \Exception("Erro ao testar GetFeature com {$count} features: " . $response->body());
            }

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            $results[] = "{$count} features: {$duration}ms";
        }

        $this->info('Resultados do GetFeature:');
        foreach ($results as $result) {
            $this->info($result);
        }
    }

    protected function testGetMapPerformance()
    {
        $this->info('Testando performance do GetMap...');

        $sizes = [
            [256, 256],
            [512, 512],
            [768, 768],
            [1024, 1024]
        ];
        $results = [];

        foreach ($sizes as [$width, $height]) {
            $startTime = microtime(true);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/{$this->workspace}/wms", [
                    'service' => 'WMS',
                    'version' => '1.1.1',
                    'request' => 'GetMap',
                    'layers' => "{$this->workspace}:municipios",
                    'bbox' => '-74.0,-33.8,-34.0,5.3',
                    'width' => $width,
                    'height' => $height,
                    'srs' => 'EPSG:4326',
                    'format' => 'image/png'
                ]);

            if (!$response->successful()) {
                throw new \Exception("Erro ao testar GetMap com tamanho {$width}x{$height}: " . $response->body());
            }

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            $results[] = "{$width}x{$height}: {$duration}ms";
        }

        $this->info('Resultados do GetMap:');
        foreach ($results as $result) {
            $this->info($result);
        }
    }

    protected function testGetFeatureInfoPerformance()
    {
        $this->info('Testando performance do GetFeatureInfo...');

        $zoomLevels = [1, 2, 4, 8];
        $results = [];

        foreach ($zoomLevels as $zoom) {
            $startTime = microtime(true);

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
                    'srs' => 'EPSG:4326',
                    'buffer' => $zoom
                ]);

            if (!$response->successful()) {
                throw new \Exception("Erro ao testar GetFeatureInfo com zoom {$zoom}: " . $response->body());
            }

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            $results[] = "Zoom {$zoom}: {$duration}ms";
        }

        $this->info('Resultados do GetFeatureInfo:');
        foreach ($results as $result) {
            $this->info($result);
        }
    }
} 