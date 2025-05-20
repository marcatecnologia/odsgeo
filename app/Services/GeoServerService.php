<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeoServerService
{
    protected string $baseUrl;
    protected string $workspace;
    protected string $layer;
    protected string $username;
    protected string $password;
    protected string $store;
    protected int $timeout;
    protected bool $cacheEnabled;
    protected int $cacheTime;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected array $geometrySettings;
    protected array $coordinateSettings;
    protected array $performanceSettings;
    protected int $paginationPerPage;
    protected string $parcelasLayer;
    protected string $municipiosLayer;
    protected string $estadosLayer;

    public function __construct()
    {
        $this->baseUrl = config('geoserver.url');
        $this->workspace = config('geoserver.workspace', 'odsgeo');
        $this->layer = config('geoserver.layer', 'parcelas_sigef_brasil');
        $this->username = config('geoserver.username', 'admin');
        $this->password = config('geoserver.password', 'geoserver');
        $this->store = config('geoserver.store', 'postgis');
        $this->timeout = config('geoserver.timeout', 30);
        $this->cacheEnabled = config('geoserver.cache.enabled', true);
        $this->cacheTime = config('geoserver.cache.time', 3600);
        $this->retryAttempts = config('geoserver.retry_attempts', 3);
        $this->retryDelay = config('geoserver.retry_delay', 1000);
        $this->geometrySettings = config('geoserver.geometry', []);
        $this->coordinateSettings = config('geoserver.coordinate', []);
        $this->performanceSettings = config('geoserver.performance', []);
        $this->paginationPerPage = config('geoserver.pagination.per_page', 50);
        $this->parcelasLayer = config('geoserver.parcelas_layer');
        $this->municipiosLayer = config('geoserver.municipios_layer');
        $this->estadosLayer = config('geoserver.estados_layer');
    }

    /**
     * Busca parcelas por município
     */
    public function getParcelasPorMunicipio($codigoMunicipio, $page = 1, $perPage = 50)
    {
        $cacheKey = "parcelas_municipio_{$codigoMunicipio}_page_{$page}_per_page_{$perPage}";
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($codigoMunicipio, $page, $perPage) {
                return $this->fetchParcelasPorMunicipio($codigoMunicipio, $page, $perPage);
            });
        }
        
        return $this->fetchParcelasPorMunicipio($codigoMunicipio, $page, $perPage);
    }

    protected function fetchParcelasPorMunicipio($codigoMunicipio, $page, $perPage)
    {
        $startIndex = ($page - 1) * $perPage;
        
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => $this->layer,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'startIndex' => $startIndex,
            'count' => $perPage,
            'propertyName' => 'codigo_imovel,ccir,nome_propriedade,area_total,cnpj,geometry',
            'cql_filter' => "codigo_municipio='{$codigoMunicipio}'"
        ];

        if ($this->geometrySettings['simplify']) {
            $params['simplify'] = $this->geometrySettings['simplify_factor'];
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0,
                'page' => $page,
                'per_page' => $perPage
            ];
        }

        Log::error('Erro ao buscar parcelas por município', [
            'municipio' => $codigoMunicipio,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar parcelas: ' . $response->status());
    }

    /**
     * Formata as parcelas retornadas pelo GeoServer
     */
    protected function formatParcelas(array $features): array
    {
        return array_map(function ($feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            return [
                'id' => $feature['id'] ?? null,
                'codigo_imovel' => $properties['codigo_imovel'] ?? null,
                'ccir' => $properties['ccir'] ?? null,
                'nome_propriedade' => $properties['nome_propriedade'] ?? null,
                'area_total' => $properties['area_total'] ?? null,
                'cnpj' => $properties['cnpj'] ?? null,
                'geometry' => $geometry
            ];
        }, $features);
    }

    /**
     * Busca parcelas por coordenada e raio
     */
    public function getParcelasPorCoordenada($latitude, $longitude, $raio, $page = 1, $perPage = 50)
    {
        $cacheKey = "parcelas_coordenada_{$latitude}_{$longitude}_{$raio}_page_{$page}_per_page_{$perPage}";
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($latitude, $longitude, $raio, $page, $perPage) {
                return $this->fetchParcelasPorCoordenada($latitude, $longitude, $raio, $page, $perPage);
            });
        }
        
        return $this->fetchParcelasPorCoordenada($latitude, $longitude, $raio, $page, $perPage);
    }

    protected function fetchParcelasPorCoordenada($latitude, $longitude, $raio, $page, $perPage)
    {
        $startIndex = ($page - 1) * $perPage;
        
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => $this->layer,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'startIndex' => $startIndex,
            'count' => $perPage,
            'propertyName' => 'codigo_imovel,ccir,nome_propriedade,area_total,cnpj,geometry',
            'cql_filter' => "DWITHIN(geometry,POINT({$longitude} {$latitude}),{$raio},meters)"
        ];

        if ($this->geometrySettings['simplify']) {
            $params['simplify'] = $this->geometrySettings['simplify_factor'];
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0,
                'page' => $page,
                'per_page' => $perPage
            ];
        }

        Log::error('Erro ao buscar parcelas por coordenada', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'raio' => $raio,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar parcelas: ' . $response->status());
    }

    /**
     * Busca parcelas por código do imóvel
     */
    public function buscarPorCodigoImovel(string $codigoImovel): array
    {
        try {
            $cacheKey = "geoserver_parcelas_codigo_{$codigoImovel}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->layer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "codigo_imovel='{$codigoImovel}'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->get($this->baseUrl . '/wfs', $params);

            if (!$response->successful()) {
                Log::error('Erro ao buscar parcela por código no GeoServer', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Erro ao buscar parcela no GeoServer'
                ];
            }

            $data = $response->json();
            
            $result = [
                'success' => true,
                'has_data' => !empty($data['features']),
                'data' => json_encode($data)
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $result, $this->cacheTime);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcela por código no GeoServer', [
                'error' => $e->getMessage(),
                'codigo_imovel' => $codigoImovel
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Erro ao buscar parcela no GeoServer'
            ];
        }
    }

    /**
     * Busca parcelas por CNPJ do proprietário
     */
    public function getParcelasPorCNPJ($cnpj)
    {
        $cacheKey = "parcelas_cnpj_{$cnpj}";
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($cnpj) {
                return $this->fetchParcelasPorCNPJ($cnpj);
            });
        }
        
        return $this->fetchParcelasPorCNPJ($cnpj);
    }

    protected function fetchParcelasPorCNPJ($cnpj)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => $this->layer,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'codigo_imovel,ccir,nome_propriedade,area_total,cnpj,geometry',
            'cql_filter' => "cnpj='{$cnpj}'"
        ];

        if ($this->geometrySettings['simplify']) {
            $params['simplify'] = $this->geometrySettings['simplify_factor'];
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];
        }

        Log::error('Erro ao buscar parcelas por CNPJ', [
            'cnpj' => $cnpj,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar parcelas: ' . $response->status());
    }

    /**
     * Busca parcelas por nome da propriedade
     */
    public function buscarPorNomePropriedade(string $nome): array
    {
        try {
            $cacheKey = "geoserver_parcelas_nome_{$nome}";
            
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => "{$this->workspace}:{$this->layer}",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4674',
                'CQL_FILTER' => "nome_propriedade ILIKE '%{$nome}%'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->get($this->baseUrl . '/wfs', $params);

            if (!$response->successful()) {
                Log::error('Erro ao buscar parcelas por nome no GeoServer', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Erro ao buscar parcelas no GeoServer'
                ];
            }

            $data = $response->json();
            
            $result = [
                'success' => true,
                'has_data' => !empty($data['features']),
                'data' => json_encode($data)
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $result, $this->cacheTime);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por nome no GeoServer', [
                'error' => $e->getMessage(),
                'nome' => $nome
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Erro ao buscar parcelas no GeoServer'
            ];
        }
    }

    /**
     * Busca parcelas por CCIR
     */
    public function getParcelasPorCCIR($ccir)
    {
        $cacheKey = "parcelas_ccir_{$ccir}";
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($ccir) {
                return $this->fetchParcelasPorCCIR($ccir);
            });
        }
        
        return $this->fetchParcelasPorCCIR($ccir);
    }

    protected function fetchParcelasPorCCIR($ccir)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => $this->layer,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'codigo_imovel,ccir,nome_propriedade,area_total,cnpj,geometry',
            'cql_filter' => "ccir='{$ccir}'"
        ];

        if ($this->geometrySettings['simplify']) {
            $params['simplify'] = $this->geometrySettings['simplify_factor'];
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];
        }

        Log::error('Erro ao buscar parcelas por CCIR', [
            'ccir' => $ccir,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar parcelas: ' . $response->status());
    }

    /**
     * Busca municípios por UF
     */
    public function getMunicipiosByUF($uf)
    {
        $cacheKey = "municipios_uf_{$uf}";
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($uf) {
                return $this->fetchMunicipiosByUF($uf);
            });
        }
        
        return $this->fetchMunicipiosByUF($uf);
    }

    protected function fetchMunicipiosByUF($uf)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => 'municipios_simplificado',
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'codigo_ibge,nome,uf',
            'cql_filter' => "uf='{$uf}'"
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            $municipios = collect($data['features'] ?? [])->map(function ($feature) {
                return [
                    'codigo' => $feature['properties']['codigo_ibge'],
                    'nome' => $feature['properties']['nome'],
                    'uf' => $feature['properties']['uf']
                ];
            })->sortBy('nome')->values()->toArray();

            return $municipios;
        }

        Log::error('Erro ao buscar municípios', [
            'uf' => $uf,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar municípios: ' . $response->status());
    }

    /**
     * Busca município por código IBGE
     */
    public function getMunicipioByCodigo(string $codigoIbge): ?array
    {
        try {
            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => 'municipios_simplificado',
                'outputFormat' => 'application/json',
                'CQL_FILTER' => "codigo_ibge = '{$codigoIbge}'"
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->get($this->baseUrl . '/wfs', $params);

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

    /**
     * Busca centróide do município
     */
    public function getMunicipioCentroide($codigoIBGE)
    {
        try {
            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetFeature',
                'typeName' => 'municipios_simplificado',
                'outputFormat' => 'application/json',
                'CQL_FILTER' => "codigo_ibge='{$codigoIBGE}'",
                'srsName' => 'EPSG:4326',
                'propertyName' => 'codigo_ibge,nome,centroide,geom_simplificado'
            ];

            if ($this->geometrySettings['simplify']) {
                $params['propertyName'] = "*,ST_Simplify(geom,{$this->geometrySettings['tolerance']}) as geom_simplificado";
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->get($this->baseUrl . '/wfs', $params);

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

    public function getParcelasPorCodigo($codigo)
    {
        $cacheKey = "parcelas_codigo_{$codigo}";
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($codigo) {
                return $this->fetchParcelasPorCodigo($codigo);
            });
        }
        
        return $this->fetchParcelasPorCodigo($codigo);
    }

    protected function fetchParcelasPorCodigo($codigo)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => $this->layer,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'codigo_imovel,ccir,nome_propriedade,area_total,cnpj,geometry',
            'cql_filter' => "codigo_imovel='{$codigo}'"
        ];

        if ($this->geometrySettings['simplify']) {
            $params['simplify'] = $this->geometrySettings['simplify_factor'];
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];
        }

        Log::error('Erro ao buscar parcelas por código', [
            'codigo' => $codigo,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar parcelas: ' . $response->status());
    }

    /**
     * Busca parcelas por nome da propriedade
     */
    public function getParcelasPorNome($nome)
    {
        $cacheKey = "parcelas_nome_" . md5($nome);
        
        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $this->cacheTime, function () use ($nome) {
                return $this->fetchParcelasPorNome($nome);
            });
        }
        
        return $this->fetchParcelasPorNome($nome);
    }

    protected function fetchParcelasPorNome($nome)
    {
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeName' => $this->layer,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326',
            'propertyName' => 'codigo_imovel,ccir,nome_propriedade,area_total,cnpj,geometry',
            'cql_filter' => "strToLowerCase(nome_propriedade) LIKE strToLowerCase('%{$nome}%')",
            'startIndex' => 0,
            'count' => $this->performanceSettings['max_results']
        ];

        if ($this->geometrySettings['simplify']) {
            $params['simplify'] = $this->geometrySettings['simplify_factor'];
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/wfs', $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'parcelas' => $this->formatParcelas($data['features'] ?? []),
                'total' => $data['totalFeatures'] ?? 0
            ];
        }

        Log::error('Erro ao buscar parcelas por nome', [
            'nome' => $nome,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar parcelas: ' . $response->status());
    }

    public function getEstados()
    {
        // Use fallback estático para garantir leveza
        return [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];
    }

    public function getMunicipios(string $uf)
    {
        $cacheKey = "municipios_{$uf}";
        return \Cache::remember($cacheKey, 3600, function () use ($uf) {
            try {
                $response = \Http::withBasicAuth($this->username, $this->password)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/{$this->workspace}/wfs", [
                        'service' => 'WFS',
                        'version' => '2.0.0',
                        'request' => 'GetFeature',
                        'typeName' => $this->municipiosLayer,
                        'outputFormat' => 'application/json',
                        'srsName' => 'EPSG:4326',
                        'CQL_FILTER' => "sigla_uf = '{$uf}'"
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return collect($data['features'])
                        ->map(function ($feature) {
                            return [
                                'codigo' => $feature['properties']['cd_mun'] ?? null,
                                'nome' => $feature['properties']['nm_mun'] ?? null
                            ];
                        })
                        ->toArray();
                }
                return [];
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    public function getParcelas(string $codigoMunicipio)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/{$this->workspace}/wfs", [
                    'service' => 'WFS',
                    'version' => '2.0.0',
                    'request' => 'GetFeature',
                    'typeName' => $this->parcelasLayer,
                    'outputFormat' => 'application/json',
                    'srsName' => 'EPSG:4326',
                    'CQL_FILTER' => "municipio_ = '{$codigoMunicipio}'"
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['features'];
            }

            \Log::error('Erro ao buscar parcelas do GeoServer', [
                'codigo_municipio' => $codigoMunicipio,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            \Log::error('Exceção ao buscar parcelas do GeoServer', [
                'codigo_municipio' => $codigoMunicipio,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
        }
    }

    public function getEstadoGeometry(string $uf)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/{$this->workspace}/wfs", [
                    'service' => 'WFS',
                    'version' => '2.0.0',
                    'request' => 'GetFeature',
                    'typeName' => $this->estadosLayer,
                    'outputFormat' => 'application/json',
                    'srsName' => 'EPSG:4326',
                    'CQL_FILTER' => "sigla_uf = '{$uf}'"
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['features'])) {
                    return [
                        'type' => 'FeatureCollection',
                        'features' => $data['features']
                    ];
                }
            }

            \Log::error('Erro ao buscar geometria do estado do GeoServer', [
                'uf' => $uf,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            \Log::error('Exceção ao buscar geometria do estado do GeoServer', [
                'uf' => $uf,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    protected function getNomeEstado(string $uf): string
    {
        $estados = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];

        return $estados[$uf] ?? $uf;
    }
} 