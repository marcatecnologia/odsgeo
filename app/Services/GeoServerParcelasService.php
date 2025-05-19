<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeoServerParcelasService
{
    protected string $baseUrl;
    protected string $workspace;
    protected string $parcelasLayer;
    protected string $municipiosLayer;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected bool $cacheEnabled;
    protected int $cacheTime;
    protected array $geometrySettings;

    public function __construct()
    {
        $this->baseUrl = config('geoserver.url', 'http://host.docker.internal:8082/geoserver');
        $this->workspace = config('geoserver.workspace', 'odsgeo');
        $this->parcelasLayer = config('geoserver.layer', 'parcelas_sigef_brasil');
        $this->username = config('geoserver.username', 'admin');
        $this->password = config('geoserver.password', 'geoserver');
        $this->timeout = config('geoserver.timeout', 30);
        $this->retryAttempts = config('geoserver.retry_attempts', 3);
        $this->retryDelay = config('geoserver.retry_delay', 1000);
        $this->cacheEnabled = config('geoserver.cache_enabled', true);
        $this->cacheTime = config('geoserver.cache_time', 3600);
        $this->geometrySettings = config('geoserver.geometry', [
            'simplify' => true,
            'tolerance' => 0.0001,
            'max_points' => 1000
        ]);

        Log::info('GeoServerParcelasService inicializado', [
            'baseUrl' => $this->baseUrl,
            'workspace' => $this->workspace,
            'parcelasLayer' => $this->parcelasLayer,
            'timeout' => $this->timeout,
            'retryAttempts' => $this->retryAttempts,
            'retryDelay' => $this->retryDelay,
            'cacheEnabled' => $this->cacheEnabled,
            'cacheTime' => $this->cacheTime
        ]);
    }

    /**
     * Faz uma requisição ao GeoServer
     */
    protected function makeRequest(string $endpoint, array $params = []): array
    {
        try {
            Log::info('Fazendo requisição ao GeoServer', [
                'endpoint' => $endpoint,
                'params' => $params
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->get($this->baseUrl . $endpoint, $params);

            if (!$response->successful()) {
                Log::error('Erro na requisição ao GeoServer', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'endpoint' => $endpoint,
                    'params' => $params
                ]);

                return [
                    'success' => false,
                    'error' => "Erro ao acessar o GeoServer: {$response->status()} - {$response->body()}"
                ];
            }

            return [
                'success' => true,
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Exceção ao fazer requisição ao GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => $endpoint,
                'params' => $params
            ]);

            return [
                'success' => false,
                'error' => "Erro ao acessar o GeoServer: {$e->getMessage()}"
            ];
        }
    }

    /**
     * Busca parcelas por município
     */
    public function getParcelasPorMunicipio(string $codigoMunicipio, int $page = 1, int $perPage = 50)
    {
        $cacheKey = "parcelas_municipio_{$codigoMunicipio}_page_{$page}";

        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($codigoMunicipio, $page, $perPage) {
                return $this->fetchParcelasPorMunicipio($codigoMunicipio, $page, $perPage);
            });
        }

        return $this->fetchParcelasPorMunicipio($codigoMunicipio, $page, $perPage);
    }

    /**
     * Busca parcelas por município diretamente do GeoServer
     */
    protected function fetchParcelasPorMunicipio(string $codigoMunicipio, int $page, int $perPage)
    {
        $startIndex = ($page - 1) * $perPage;

        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => "{$this->workspace}:{$this->parcelasLayer}",
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'startIndex' => $startIndex,
            'count' => $perPage,
            'CQL_FILTER' => "codigo_municipio = '{$codigoMunicipio}'"
        ];

        if ($this->geometrySettings['simplify']) {
            $params['CQL_FILTER'] .= " AND simplify(geometry, {$this->geometrySettings['tolerance']}) IS NOT NULL";
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/wfs", $params);

                if ($response->successful()) {
                    return $response->json();
                }

                throw new \Exception("Erro na requisição WFS: " . $response->body());
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        Log::error('Erro ao buscar parcelas por município', [
            'codigo_municipio' => $codigoMunicipio,
            'error' => $lastError->getMessage(),
            'attempts' => $attempt
        ]);

        throw $lastError;
    }

    /**
     * Busca parcelas por código do imóvel
     */
    public function getParcelasPorCodigo(string $codigoImovel): array
    {
        try {
            $cacheKey = "parcelas_codigo_{$codigoImovel}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->parcelasLayer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "codigo_imo='{$codigoImovel}'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $result = $this->makeRequest('/wfs', $params);

            if (!$result['success']) {
                return $result;
            }

            $data = $result['data'];
            
            $response = [
                'success' => true,
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $response, $this->cacheTime);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcela por código', [
                'error' => $e->getMessage(),
                'codigo' => $codigoImovel
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca parcelas por CCIR
     */
    public function getParcelasPorCCIR(string $ccir): array
    {
        try {
            $cacheKey = "parcelas_ccir_{$ccir}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->parcelasLayer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "ccir='{$ccir}'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $result = $this->makeRequest('/wfs', $params);

            if (!$result['success']) {
                return $result;
            }

            $data = $result['data'];
            
            $response = [
                'success' => true,
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $response, $this->cacheTime);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por CCIR', [
                'error' => $e->getMessage(),
                'ccir' => $ccir
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca parcelas por CNPJ
     */
    public function getParcelasPorCNPJ(string $cnpj): array
    {
        try {
            $cacheKey = "parcelas_cnpj_{$cnpj}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->parcelasLayer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "cnpj='{$cnpj}'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $result = $this->makeRequest('/wfs', $params);

            if (!$result['success']) {
                return $result;
            }

            $data = $result['data'];
            
            $response = [
                'success' => true,
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $response, $this->cacheTime);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por CNPJ', [
                'error' => $e->getMessage(),
                'cnpj' => $cnpj
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca parcelas por nome da propriedade
     */
    public function getParcelasPorNome(string $nome): array
    {
        try {
            $cacheKey = "parcelas_nome_{$nome}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->parcelasLayer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "nome_area ILIKE '%{$nome}%'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $result = $this->makeRequest('/wfs', $params);

            if (!$result['success']) {
                return $result;
            }

            $data = $result['data'];
            
            $response = [
                'success' => true,
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $response, $this->cacheTime);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por nome', [
                'error' => $e->getMessage(),
                'nome' => $nome
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca parcelas por coordenada e raio
     */
    public function getParcelasPorCoordenada(float $latitude, float $longitude, float $raio): array
    {
        try {
            $cacheKey = "parcelas_coord_{$latitude}_{$longitude}_{$raio}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->parcelasLayer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "INTERSECTS(geom, BUFFER(POINT({$longitude} {$latitude}), {$raio}))"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $result = $this->makeRequest('/wfs', $params);

            if (!$result['success']) {
                return $result;
            }

            $data = $result['data'];
            
            $response = [
                'success' => true,
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $response, $this->cacheTime);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por coordenada', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca municípios por UF
     */
    public function getMunicipiosPorUF($uf)
    {
        $cacheKey = "geoserver_municipios_uf_{$uf}";
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($uf) {
                return $this->fetchMunicipiosPorUF($uf);
            });
        }
        return $this->fetchMunicipiosPorUF($uf);
    }

    protected function fetchMunicipiosPorUF($uf)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => "{$this->workspace}:br_municipios_2024",
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'cd_mun,nm_mun,sigla_uf',
            'CQL_FILTER' => "sigla_uf = '{$uf}'"
        ];
        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get("{$this->baseUrl}/wfs", $params);
        if ($response->successful()) {
            $data = $response->json();
            // Retorna array de objetos com codigo_ibge e nome
            return collect($data['features'] ?? [])->map(function($feature) {
                return (object) [
                    'codigo_ibge' => $feature['properties']['cd_mun'],
                    'nome' => $feature['properties']['nm_mun']
                ];
            })->toArray();
        }
        return [];
    }

    /**
     * Busca municípios por estado
     */
    public function getMunicipiosPorEstado(string $uf)
    {
        $cacheKey = "municipios_estado_{$uf}";

        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($uf) {
                return $this->fetchMunicipiosPorEstado($uf);
            });
        }

        return $this->fetchMunicipiosPorEstado($uf);
    }

    /**
     * Busca municípios por estado diretamente do GeoServer
     */
    protected function fetchMunicipiosPorEstado(string $uf)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => "{$this->workspace}:{$this->municipiosLayer}",
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'CQL_FILTER' => "uf = '{$uf}'"
        ];

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/wfs", $params);

                if ($response->successful()) {
                    return $response->json();
                }

                throw new \Exception("Erro na requisição WFS: " . $response->body());
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        Log::error('Erro ao buscar municípios por estado', [
            'uf' => $uf,
            'error' => $lastError->getMessage(),
            'attempts' => $attempt
        ]);

        throw $lastError;
    }

    /**
     * Formata as parcelas retornadas pelo GeoServer
     */
    protected function formatParcelas(array $features): array
    {
        return array_map(function($feature) {
            return [
                'parcela_co' => $feature['properties']['parcela_co'] ?? null,
                'codigo_imo' => $feature['properties']['codigo_imo'] ?? null,
                'ccir' => $feature['properties']['ccir'] ?? null,
                'nome_area' => $feature['properties']['nome_area'] ?? null,
                'area_total' => $feature['properties']['area_total'] ?? null,
                'cnpj' => $feature['properties']['cnpj'] ?? null,
                'municipio_' => $feature['properties']['municipio_'] ?? null,
                'uf_id' => $feature['properties']['uf_id'] ?? null,
                'geometry' => $feature['geometry'] ?? null
            ];
        }, $features);
    }

    /**
     * Busca lista de estados (UF) do GeoServer
     */
    public function getEstados()
    {
        $cacheKey = 'geoserver_estados';
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () {
                return $this->fetchEstados();
            });
        }
        return $this->fetchEstados();
    }

    protected function fetchEstados()
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => "{$this->workspace}:br_uf_2024",
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'sigla_uf,nm_uf'
        ];
        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get("{$this->baseUrl}/wfs", $params);
        if ($response->successful()) {
            $data = $response->json();
            // Retorna array de siglas e nomes
            return collect($data['features'] ?? [])->mapWithKeys(function($feature) {
                return [$feature['properties']['sigla_uf'] => $feature['properties']['nm_uf']];
            })->toArray();
        }
        return [];
    }
} 