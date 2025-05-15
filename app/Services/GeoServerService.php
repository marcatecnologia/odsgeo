<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeoServerService
{
    protected $baseUrl;
    protected $workspace;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = config('geoserver.url', 'http://localhost:8082/geoserver');
        $this->workspace = config('geoserver.workspace', 'odsgeo');
        $this->username = config('geoserver.username', 'admin');
        $this->password = config('geoserver.password', 'geoserver');
    }

    public function getMunicipiosByUF(string $uf): array
    {
        $cacheKey = "municipios_uf_{$uf}";
        
        return Cache::remember($cacheKey, 3600, function () use ($uf) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                        'service' => 'WFS',
                        'version' => '2.0.0',
                        'request' => 'GetFeature',
                        'typeName' => 'municipios_simplificado',
                        'outputFormat' => 'application/json',
                        'CQL_FILTER' => "uf = '{$uf}'"
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Erro ao buscar municípios no GeoServer', [
                    'uf' => $uf,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return ['features' => []];
            } catch (\Exception $e) {
                Log::error('Exceção ao buscar municípios no GeoServer', [
                    'uf' => $uf,
                    'error' => $e->getMessage()
                ]);

                return ['features' => []];
            }
        });
    }

    public function getMunicipioByCodigo(string $codigoIbge): ?array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                    'service' => 'WFS',
                    'version' => '2.0.0',
                    'request' => 'GetFeature',
                    'typeName' => 'municipios_simplificado',
                    'outputFormat' => 'application/json',
                    'CQL_FILTER' => "codigo_ibge = '{$codigoIbge}'"
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['features'][0] ?? null;
            }

            Log::error('Erro ao buscar município no GeoServer', [
                'codigo_ibge' => $codigoIbge,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exceção ao buscar município no GeoServer', [
                'codigo_ibge' => $codigoIbge,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function getMunicipioCentroide($codigoIBGE)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(config('geoserver.timeout'))
                ->retry(config('geoserver.retry_attempts'), config('geoserver.retry_delay'))
                ->get("{$this->baseUrl}/{$this->workspace}/ows", [
                    'service' => 'WFS',
                    'version' => '2.0.0',
                    'request' => 'GetFeature',
                    'typeName' => 'municipios_simplificado',
                    'outputFormat' => 'application/json',
                    'CQL_FILTER' => "codigo_ibge='{$codigoIBGE}'",
                    'srsName' => 'EPSG:4326',
                    'propertyName' => 'codigo_ibge,nome,centroide,geom_simplificado'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['features'])) {
                    return [
                        'centroide' => $data['features'][0]['properties']['centroide'],
                        'geometry' => $data['features'][0]['geometry']
                    ];
                }
            }

            Log::error('Erro ao buscar centróide no GeoServer', [
                'codigo_ibge' => $codigoIBGE,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            throw new \Exception('Erro ao buscar centróide no GeoServer: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('Exceção ao buscar centróide no GeoServer', [
                'codigo_ibge' => $codigoIBGE,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 