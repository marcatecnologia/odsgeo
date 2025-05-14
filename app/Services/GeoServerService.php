<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoServerService
{
    protected $baseUrl;
    protected $workspace;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = config('geoserver.url');
        $this->workspace = config('geoserver.workspace');
        $this->username = config('geoserver.username');
        $this->password = config('geoserver.password');
    }

    public function getMunicipiosByUF($uf)
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
                    'CQL_FILTER' => "uf='{$uf}'",
                    'srsName' => 'EPSG:4326',
                    'propertyName' => 'codigo_ibge,nome,uf,centroide,geom_simplificado'
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar municípios no GeoServer', [
                'uf' => $uf,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            throw new \Exception('Erro ao buscar municípios no GeoServer: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('Exceção ao buscar municípios no GeoServer', [
                'uf' => $uf,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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