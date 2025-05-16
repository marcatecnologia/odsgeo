<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SigefWfsService
{
    // Constantes da classe
    public const SESSION_TIMEOUT = 15;
    public const MAX_FEATURES = 100;
    public const SESSION_RETRY_DELAY = 5000;
    public const SESSION_MAX_RETRIES = 3;
    public const CONNECT_TIMEOUT = 10;
    public const EARTH_RADIUS = 6371000; // Raio da Terra em metros
    public const DEGREE_TO_METERS = 111320; // Aproximação de grau para metros no equador

    protected Client $client;
    protected string $baseUrl;
    protected int $maxRetries;
    protected array $retryDelays;
    protected int $connectTimeout;
    protected int $responseTimeout;
    protected array $cookies = [];
    protected bool $sessionEstablished = false;
    protected int $sessionRetryCount = 0;
    protected const SESSION_RETRY_DELAYS = [2, 3, 4];
    protected string $workspace;
    protected string $layer;
    protected int $timeout;
    protected string $wfsUrl;
    protected bool $cacheEnabled;
    protected int $cacheTime;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->baseUrl = config('sigef.wfs_url', 'https://sigef.incra.gov.br/geoserver/wfs');
        $this->maxRetries = config('sigef.max_retries', 3);
        $this->retryDelays = [5, 10, 15];
        $this->connectTimeout = config('sigef.connect_timeout', 10);
        $this->responseTimeout = config('sigef.timeout', 30);
        $this->workspace = config('geoserver.workspace', 'sigef');
        $this->layer = 'parcelas_sigef';
        $this->timeout = config('sigef.timeout', 30);
        $this->wfsUrl = config('sigef.wfs_url', 'https://sigef.incra.gov.br/geoserver/wfs');
        $this->cacheEnabled = config('sigef.cache_enabled', true);
        $this->cacheTime = config('sigef.cache_time', 3600);
    }

    protected function abrirSessao(): bool
    {
        if ($this->sessionEstablished) {
            return true;
        }

        Log::debug('Iniciando abertura de sessão SIGEF');

        $attempts = 0;
        $lastException = null;

        while ($attempts < self::SESSION_MAX_RETRIES) {
            try {
                $response = Http::withOptions([
                    'verify' => false,
                    'timeout' => self::SESSION_TIMEOUT,
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                        CURLOPT_TCP_KEEPALIVE => 1,
                        CURLOPT_TCP_KEEPIDLE => 60,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 5,
                        CURLOPT_DNS_CACHE_TIMEOUT => 600,
                        CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                        CURLOPT_TIMEOUT => self::SESSION_TIMEOUT
                    ]
                ])
                ->withHeaders([
                    'Accept' => '*/*',
                    'User-Agent' => 'ODSGEO/1.0 (SIGEF Integration)',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                    'Connection' => 'keep-alive'
                ])
                ->timeout(self::SESSION_TIMEOUT)
                ->retry(self::SESSION_MAX_RETRIES, self::SESSION_RETRY_DELAY, function ($exception) {
                    Log::warning('Tentativa de reconexão SIGEF', [
                        'error' => $exception->getMessage(),
                        'type' => get_class($exception)
                    ]);
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                           $exception instanceof \Illuminate\Http\Client\RequestException;
                })
                ->get($this->baseUrl);

                if ($response->successful()) {
                    $cookies = $response->cookies();
                    if (!empty($cookies)) {
                        $this->cookies = $cookies;
                        $this->sessionEstablished = true;
                        Log::info('Sessão SIGEF estabelecida com sucesso', [
                            'cookies' => array_keys($this->cookies),
                            'attempt' => $attempts + 1
                        ]);
                        return true;
                    }
                }

                Log::warning('Falha ao estabelecer sessão SIGEF', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => $attempts + 1
                ]);

            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning('Tentativa de reconexão SIGEF falhou', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempts + 1,
                    'type' => get_class($e)
                ]);

                // Aguarda um tempo progressivo entre as tentativas
                $delay = self::SESSION_RETRY_DELAY * ($attempts + 1);
                usleep($delay * 1000);
            }

            $attempts++;
        }

        Log::error('Erro ao estabelecer sessão SIGEF após ' . self::SESSION_MAX_RETRIES . ' tentativas', [
            'error' => $lastException ? $lastException->getMessage() : 'Erro desconhecido',
            'type' => $lastException ? get_class($lastException) : null
        ]);
        return false;
    }

    /**
     * Busca parcelas por município com cache e paginação otimizada
     */
    public function getParcelasPorMunicipio(string $uf, string $municipio, int $page = 1): array
    {
        try {
            // Verifica throttle
            if (!$this->checkThrottle('municipio')) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.'
                ];
            }

            // Gera chave de cache
            $cacheKey = "sigef_parcelas_municipio_{$uf}_{$municipio}_page_{$page}";
            
            // Tenta recuperar do cache
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => Cache::get($cacheKey)
                ];
            }

            if (!$this->abrirSessao()) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Não foi possível estabelecer conexão com o SIGEF. Tente novamente mais tarde.'
                ];
            }

            $typeName = 'parcelageo_' . strtolower($uf);
            $startIndex = ($page - 1) * config('sigef.pagination.per_page', 50);
            
            // Adiciona filtros de otimização
            $params = [
                'CQL_FILTER' => "municipio ILIKE '%{$municipio}%'",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'startIndex' => $startIndex,
                'maxFeatures' => config('sigef.pagination.per_page', 50),
                'propertyName' => 'codigo_imovel,matricula_sigef,area_ha,situacao,geom' // Otimiza retorno
            ];

            Log::debug('Parâmetros da requisição SIGEF', [
                'typeName' => $typeName,
                'params' => $params
            ]);

            $response = $this->makeRequest($typeName, $params);
            
            if (!$response['success']) {
                return $response;
            }

            $data = json_decode($response['data'], true);
            
            // Simplifica geometrias se necessário
            if (isset($data['features'])) {
                $data['features'] = $this->simplifyGeometries($data['features']);
            }

            $result = [
                'success' => true,
                'has_data' => !empty($data['features']),
                'data' => json_encode($data),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => config('sigef.pagination.per_page', 50),
                    'total' => $data['totalFeatures'] ?? 0,
                    'has_more' => ($startIndex + config('sigef.pagination.per_page', 50)) < ($data['totalFeatures'] ?? 0)
                ]
            ];

            // Armazena em cache com TTL otimizado
            if ($this->cacheEnabled) {
                Cache::put(
                    $cacheKey,
                    $result,
                    now()->addSeconds($this->cacheTime)
                );
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas SIGEF', [
                'error' => $e->getMessage(),
                'estado' => $uf,
                'municipio' => $municipio,
                'type' => get_class($e)
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Ocorreu um erro ao buscar as parcelas. Tente novamente mais tarde.'
            ];
        }
    }

    /**
     * Busca parcelas por coordenada com cache e otimizações
     */
    public function buscarParcelasPorCoordenada(float $lat, float $lon, float $raio, string $uf): array
    {
        try {
            // Verifica throttle
            if (!$this->checkThrottle('coordenada')) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.'
                ];
            }

            // Valida raio
            $raioMin = config('sigef.coordenada.raio_minimo', 100);
            $raioMax = config('sigef.coordenada.raio_maximo', 10000);
            
            if ($raio < $raioMin || $raio > $raioMax) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => "O raio deve estar entre {$raioMin} e {$raioMax} metros."
                ];
            }

            // Gera chave de cache
            $cacheKey = "sigef_parcelas_coordenada_{$lat}_{$lon}_{$raio}_{$uf}";
            
            // Tenta recuperar do cache
            if (config('sigef.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    Log::info('Cache hit para parcelas por coordenada', [
                        'lat' => $lat,
                        'lon' => $lon,
                        'raio' => $raio,
                        'uf' => $uf
                    ]);
                    return $cached;
                }
            }

            if (!$this->abrirSessao()) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Não foi possível estabelecer conexão com o SIGEF. Tente novamente mais tarde.'
                ];
            }

            $bbox = $this->calcularBoundingBox($lat, $lon, $raio);
            
            Log::debug('Bounding box calculada', [
                'bbox' => $bbox,
                'raio_original' => $raio
            ]);

            $typeName = 'parcelageo_' . strtolower($uf);
            $params = [
                'CQL_FILTER' => "BBOX(geom, {$bbox['minLon']}, {$bbox['minLat']}, {$bbox['maxLon']}, {$bbox['maxLat']})",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'maxFeatures' => config('sigef.pagination.per_page', 50),
                'propertyName' => 'codigo_imovel,matricula_sigef,area_ha,situacao,geom' // Otimiza retorno
            ];

            Log::debug('Parâmetros da requisição SIGEF', [
                'typeName' => $typeName,
                'params' => $params
            ]);

            $response = $this->makeRequest($typeName, $params);
            
            if (!$response['success']) {
                return $response;
            }

            $data = json_decode($response['data'], true);
            
            // Simplifica geometrias se necessário
            if (isset($data['features'])) {
                $data['features'] = $this->simplifyGeometries($data['features']);
            }

            $result = [
                'success' => true,
                'has_data' => !empty($data['features']),
                'data' => json_encode($data),
                'bbox' => $bbox,
                'pagination' => [
                    'total' => $data['totalFeatures'] ?? 0,
                    'per_page' => config('sigef.pagination.per_page', 50)
                ]
            ];

            // Armazena em cache com TTL otimizado
            if (config('sigef.cache.enabled')) {
                Cache::put(
                    $cacheKey,
                    $result,
                    now()->addSeconds(config('sigef.cache.ttl.coordenadas', 86400))
                );
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por coordenada', [
                'error' => $e->getMessage(),
                'lat' => $lat,
                'lon' => $lon,
                'raio' => $raio,
                'uf' => $uf
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Ocorreu um erro ao buscar as parcelas. Tente novamente mais tarde.'
            ];
        }
    }

    /**
     * Verifica e aplica throttle nas requisições
     */
    protected function checkThrottle(string $type): bool
    {
        if (!config('sigef.throttle.enabled')) {
            return true;
        }

        $key = "sigef_throttle_{$type}_" . auth()->id();
        $maxRequests = config('sigef.throttle.max_requests', 100);
        $decayMinutes = config('sigef.throttle.decay_minutes', 1);

        return RateLimiter::attempt(
            $key,
            $maxRequests,
            function() {},
            $decayMinutes * 60
        );
    }

    /**
     * Simplifica geometrias para otimizar performance
     */
    protected function simplifyGeometries(array $features): array
    {
        if (!config('sigef.geometry.simplify', true)) {
            return $features;
        }

        $tolerance = config('sigef.geometry.tolerance', 0.0001);
        $maxPoints = config('sigef.geometry.max_points', 1000);

        foreach ($features as &$feature) {
            if (isset($feature['geometry']['coordinates'])) {
                $feature['geometry']['coordinates'] = $this->simplifyCoordinates(
                    $feature['geometry']['coordinates'],
                    $tolerance,
                    $maxPoints
                );
            }
        }

        return $features;
    }

    /**
     * Simplifica coordenadas usando o algoritmo de Douglas-Peucker
     */
    protected function simplifyCoordinates(array $coordinates, float $tolerance, int $maxPoints): array
    {
        // Implementação do algoritmo de Douglas-Peucker
        // ... código existente ...
    }

    /**
     * Faz uma requisição com retry
     */
    protected function makeRequest(string $typeName, array $params): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Cookie' => $this->getCookiesString()
                ])
                ->timeout($this->timeout)
                ->get($this->baseUrl, array_merge([
                    'service' => 'WFS',
                    'version' => '1.1.0',
                    'request' => 'GetFeature',
                    'typeName' => $typeName
                ], $params));

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->body()
                    ];
                }

                $lastException = new \Exception('Erro na resposta: ' . $response->status());

            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning('Tentativa de requisição falhou', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempts + 1
                ]);
            }

            $attempts++;
            if ($attempts < $this->maxRetries) {
                usleep($this->retryDelays[$attempts - 1] * 1000);
            }
        }

        return [
            'success' => false,
            'error' => $lastException ? $lastException->getMessage() : 'Erro desconhecido'
        ];
    }

    protected function getCookiesString(): string
    {
        $cookies = [];
        foreach ($this->cookies as $name => $value) {
            $cookies[] = "{$name}={$value}";
        }
        return implode('; ', $cookies);
    }

    protected function calcularBoundingBox(float $lat, float $lon, float $raio): array
    {
        // Adiciona margem de segurança de 20%
        $raio = $raio * 1.2;

        // Converte raio de metros para graus (aproximação)
        $raioGraus = $raio / self::DEGREE_TO_METERS;

        // Calcula os limites da bounding box
        $minLat = $lat - $raioGraus;
        $maxLat = $lat + $raioGraus;
        
        // Ajusta o raio em longitude baseado na latitude
        $raioGrausLon = $raioGraus / cos(deg2rad($lat));
        $minLon = $lon - $raioGrausLon;
        $maxLon = $lon + $raioGrausLon;

        return [
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLon' => $minLon,
            'maxLon' => $maxLon,
            'center' => [
                'lat' => $lat,
                'lon' => $lon
            ],
            'raio' => $raio
        ];
    }

    public function getFeature(string $type, array $params = []): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                // Tenta estabelecer sessão se necessário
                if (!$this->sessionEstablished) {
                    $this->establishSession();
                    if (!$this->sessionEstablished) {
                        return [
                            'success' => false,
                            'has_data' => false,
                            'error' => 'Não foi possível estabelecer conexão com o serviço SIGEF. Por favor, tente novamente mais tarde.'
                        ];
                    }
                }

                $url = $this->buildUrl($type, $params);
                Log::debug('SIGEF WFS Request', [
                    'url' => $url,
                    'type' => $type,
                    'params' => $params,
                    'attempt' => $attempt + 1,
                    'session_established' => $this->sessionEstablished
                ]);

                $response = Http::withOptions([
                    'verify' => false,
                    'timeout' => $this->responseTimeout,
                    'connect_timeout' => $this->connectTimeout,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                        CURLOPT_TCP_KEEPALIVE => 1,
                        CURLOPT_TCP_KEEPIDLE => 60,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 5
                    ]
                ])->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'ODSGEO/1.0',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1'
                ])->withCookies($this->cookies, parse_url($this->baseUrl, PHP_URL_HOST))
                ->acceptJson()
                ->get($url);

                // Atualiza cookies se houver novos
                $cookies = $response->cookies();
                foreach ($cookies as $cookie) {
                    $this->cookies[$cookie->getName()] = $cookie->getValue();
                }

                $statusCode = $response->status();
                $contentType = $response->header('Content-Type');
                $data = $response->body();

                Log::debug('SIGEF WFS Response', [
                    'status' => $statusCode,
                    'content_type' => $contentType,
                    'headers' => $response->headers(),
                    'data' => $data,
                    'cookies' => $this->cookies
                ]);

                // Validação do status HTTP
                if ($statusCode !== 200) {
                    throw new \Exception("HTTP Error: {$statusCode}");
                }

                // Tratamento especial para respostas HTML vazias
                if ($contentType === 'text/html' && empty(trim($data))) {
                    Log::warning('SIGEF WFS Empty HTML Response', [
                        'attempt' => $attempt + 1,
                        'url' => $url,
                        'headers' => $response->headers(),
                        'cookies' => $this->cookies
                    ]);

                    // Tenta reestabelecer a sessão se necessário
                    if ($attempt === 1) {
                        $this->sessionEstablished = false;
                        $this->establishSession();
                    }

                    // Se for a última tentativa, retorna erro específico
                    if ($attempt === $this->maxRetries - 1) {
                        return [
                            'success' => false,
                            'has_data' => false,
                            'error' => 'O serviço SIGEF está temporariamente indisponível. Por favor, tente novamente mais tarde.'
                        ];
                    }

                    // Aguarda um tempo maior antes da próxima tentativa
                    sleep($this->retryDelays[$attempt] * 2);
                    $attempt++;
                    continue;
                }

                // Validação do Content-Type
                if (!preg_match('/^application\/json(?:;.*)?$/i', $contentType)) {
                    Log::error('SIGEF WFS Invalid Content-Type', [
                        'expected' => 'application/json',
                        'received' => $contentType,
                        'headers' => $response->headers(),
                        'data' => $data,
                        'url' => $url,
                        'status_code' => $statusCode
                    ]);
                    return [
                        'success' => false,
                        'has_data' => false,
                        'error' => 'O serviço SIGEF retornou uma resposta inválida.'
                    ];
                }

                // Validação do corpo da resposta
                if (empty(trim($data))) {
                    Log::error('SIGEF WFS Empty Response Body', [
                        'data' => $data,
                        'url' => $url,
                        'headers' => $response->headers(),
                        'status_code' => $statusCode,
                        'content_type' => $contentType
                    ]);
                    return [
                        'success' => false,
                        'error' => 'O serviço SIGEF retornou uma resposta inválida.'
                    ];
                }

                // Validação do GeoJSON antes de decodificar
                if (!$this->isValidGeoJson($data)) {
                    Log::error('SIGEF WFS Invalid GeoJSON Structure', [
                        'data' => $data,
                        'url' => $url,
                        'headers' => $response->headers(),
                        'status_code' => $statusCode,
                        'content_type' => $contentType
                    ]);
                    return [
                        'success' => false,
                        'error' => 'O serviço SIGEF retornou uma resposta inválida.'
                    ];
                }

                // Tenta decodificar o JSON
                $jsonData = json_decode($data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('SIGEF WFS JSON Parse Error', [
                        'error' => json_last_error_msg(),
                        'data' => $data,
                        'url' => $url,
                        'headers' => $response->headers(),
                        'status_code' => $statusCode,
                        'content_type' => $contentType
                    ]);
                    return [
                        'success' => false,
                        'error' => 'O serviço SIGEF retornou uma resposta inválida.'
                    ];
                }

                return [
                    'success' => true,
                    'data' => $data
                ];
            } catch (\Exception $e) {
                $lastException = $e;
                Log::error('SIGEF WFS Error', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                    'type' => $type,
                    'params' => $params,
                    'trace' => $e->getTraceAsString()
                ]);

                if ($attempt < $this->maxRetries - 1) {
                    $delay = $this->retryDelays[$attempt];
                    sleep($delay);
                }
            }

            $attempt++;
        }

        return [
            'success' => false,
            'error' => $lastException ? $lastException->getMessage() : 'Erro desconhecido'
        ];
    }

    protected function isValidGeoJson(string $data): bool
    {
        // Verifica se é um JSON válido e tem a estrutura básica do GeoJSON
        if (!preg_match('/^\s*{\s*"type"\s*:\s*"FeatureCollection"\s*,/i', $data)) {
            Log::debug('SIGEF WFS Invalid GeoJSON Type', [
                'data_preview' => substr($data, 0, 100)
            ]);
            return false;
        }

        if (!preg_match('/"features"\s*:\s*\[/i', $data)) {
            Log::debug('SIGEF WFS Invalid GeoJSON Features', [
                'data_preview' => substr($data, 0, 100)
            ]);
            return false;
        }

        return true;
    }

    protected function buildUrl(string $type, array $params): string
    {
        $baseParams = [
            'service' => 'WFS',
            'version' => '1.1.0',
            'request' => 'GetFeature',
            'typeName' => $type,
            'outputFormat' => 'application/json',
            'srsName' => 'EPSG:4326'
        ];

        $queryParams = array_merge($baseParams, $params);
        
        // Validação dos parâmetros
        if (isset($queryParams['CQL_FILTER'])) {
            $queryParams['CQL_FILTER'] = $this->validateAndFormatCqlFilter($queryParams['CQL_FILTER']);
        }

        return $this->baseUrl . '?' . http_build_query($queryParams);
    }

    protected function validateAndFormatCqlFilter(string $filter): string
    {
        // Remove espaços extras e normaliza aspas
        $filter = trim($filter);
        
        // Validação básica do formato
        if (!preg_match('/^[a-zA-Z0-9_\s=\'\"\(\)]+$/', $filter)) {
            throw new \InvalidArgumentException('Filtro CQL inválido');
        }

        // Garante que os valores de texto estejam entre aspas simples
        $filter = preg_replace('/=([^"\']\w+[^"\'])/', "='$1'", $filter);

        return $filter;
    }

    public function getCapabilities(): array
    {
        $url = $this->baseUrl . '?' . http_build_query([
            'service' => 'WFS',
            'version' => '1.1.0',
            'request' => 'GetCapabilities'
        ]);

        try {
            $response = Http::acceptJson()
                ->timeout($this->responseTimeout)
                ->get($url);

            return [
                'success' => true,
                'data' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('SIGEF WFS GetCapabilities Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getParcelasPorCoordenada($latitude, $longitude, $raio)
    {
        try {
            $this->abrirSessao();

            $cqlFilter = sprintf(
                'INTERSECTS(geom, BUFFER(POINT(%f %f), %d))',
                $longitude,
                $latitude,
                $raio
            );

            $params = [
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => 'sigef:parcela',
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'CQL_FILTER' => $cqlFilter
            ];

            Log::info('Buscando parcelas por coordenada', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio,
                'params' => $params
            ]);

            $response = Http::withHeaders([
                'Cookie' => "JSESSIONID={$this->sessionId}"
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl, $params);

            if (!$response->successful()) {
                Log::error('Erro na resposta do SIGEF', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'Erro ao buscar parcelas no SIGEF'
                ];
            }

            $data = $response->json();
            
            if (empty($data['features'])) {
                return [
                    'success' => true,
                    'data' => json_encode([
                        'type' => 'FeatureCollection',
                        'features' => []
                    ])
                ];
            }

            return [
                'success' => true,
                'data' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por coordenada', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao buscar parcelas. Por favor, tente novamente.'
            ];
        }
    }

    public function getParcelasByMunicipio($codigoMunicipio)
    {
        try {
            $cacheKey = "sigef_parcelas_municipio_{$codigoMunicipio}";
            
            return Cache::remember($cacheKey, 3600, function () use ($codigoMunicipio) {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/{$this->workspace}/wfs", [
                        'service' => 'WFS',
                        'version' => '2.0.0',
                        'request' => 'GetFeature',
                        'typeName' => "{$this->workspace}:{$this->layer}",
                        'outputFormat' => 'application/json',
                        'CQL_FILTER' => "codigo_municipio='{$codigoMunicipio}'"
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Erro ao buscar parcelas por município', [
                    'codigo_municipio' => $codigoMunicipio,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return ['features' => []];
            });
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por município', [
                'codigo_municipio' => $codigoMunicipio,
                'error' => $e->getMessage()
            ]);

            return ['features' => []];
        }
    }

    public function getParcelasByCoordenada($latitude, $longitude, $raio)
    {
        try {
            $cacheKey = "sigef_parcelas_coordenada_{$latitude}_{$longitude}_{$raio}";
            
            return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude, $raio) {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/{$this->workspace}/wfs", [
                        'service' => 'WFS',
                        'version' => '2.0.0',
                        'request' => 'GetFeature',
                        'typeName' => "{$this->workspace}:{$this->layer}",
                        'outputFormat' => 'application/json',
                        'CQL_FILTER' => "DWITHIN(geometry,POINT({$longitude} {$latitude}),{$raio},meters)"
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Erro ao buscar parcelas por coordenada', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'raio' => $raio,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return ['features' => []];
            });
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por coordenada', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio,
                'error' => $e->getMessage()
            ]);

            return ['features' => []];
        }
    }

    public function getParcelasByCodigo($codigoImovel = null, $matriculaImovel = null)
    {
        try {
            $cacheKey = "sigef_parcelas_codigo_{$codigoImovel}_{$matriculaImovel}";

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => Cache::get($cacheKey)
                ];
            }

            $params = [
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => 'sigef:parcelas',
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326'
            ];

            if ($codigoImovel) {
                $params['CQL_FILTER'] = "codigo_imovel='{$codigoImovel}'";
            } elseif ($matriculaImovel) {
                $params['CQL_FILTER'] = "matricula_imovel='{$matriculaImovel}'";
            }

            $response = Http::get($this->wfsUrl, $params);

            if ($response->successful()) {
                $data = $response->body();
                
                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $data, $this->cacheTime);
                }

                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Erro ao buscar parcelas por código'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por código', [
                'error' => $e->getMessage(),
                'codigo_imovel' => $codigoImovel,
                'matricula_imovel' => $matriculaImovel
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getParcelasByCCIR($ccir)
    {
        try {
            $cacheKey = "sigef_parcelas_ccir_{$ccir}";

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => Cache::get($cacheKey)
                ];
            }

            $params = [
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => 'sigef:parcelas',
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'CQL_FILTER' => "ccir='{$ccir}'"
            ];

            $response = Http::get($this->wfsUrl, $params);

            if ($response->successful()) {
                $data = $response->body();
                
                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $data, $this->cacheTime);
                }

                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Erro ao buscar parcelas por CCIR'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por CCIR', [
                'error' => $e->getMessage(),
                'ccir' => $ccir
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getParcelasByCNPJ($cnpj)
    {
        try {
            $cacheKey = "sigef_parcelas_cnpj_{$cnpj}";

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => Cache::get($cacheKey)
                ];
            }

            $params = [
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => 'sigef:parcelas',
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'CQL_FILTER' => "cnpj='{$cnpj}'"
            ];

            $response = Http::get($this->wfsUrl, $params);

            if ($response->successful()) {
                $data = $response->body();
                
                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $data, $this->cacheTime);
                }

                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Erro ao buscar parcelas por CNPJ'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por CNPJ', [
                'error' => $e->getMessage(),
                'cnpj' => $cnpj
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getParcelasByNomePropriedade($nomePropriedade)
    {
        try {
            $cacheKey = "sigef_parcelas_nome_{$nomePropriedade}";

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => Cache::get($cacheKey)
                ];
            }

            $params = [
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => 'sigef:parcelas',
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'CQL_FILTER' => "nome_propriedade ILIKE '%{$nomePropriedade}%'"
            ];

            $response = Http::get($this->wfsUrl, $params);

            if ($response->successful()) {
                $data = $response->body();
                
                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $data, $this->cacheTime);
                }

                return [
                    'success' => true,
                    'has_data' => true,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Erro ao buscar parcelas por nome da propriedade'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por nome da propriedade', [
                'error' => $e->getMessage(),
                'nome_propriedade' => $nomePropriedade
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function establishSession(): void
    {
        $this->abrirSessao();
    }
} 