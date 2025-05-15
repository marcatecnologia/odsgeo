<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
    protected $workspace;
    protected $layer;
    protected $timeout;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->baseUrl = config('services.sigef.wfs_url', 'https://acervofundiario.incra.gov.br/i3geo/ogc.php');
        $this->maxRetries = 3;
        $this->retryDelays = [5, 10, 15];
        $this->connectTimeout = 5;
        $this->responseTimeout = 15;
        $this->workspace = config('geoserver.workspace');
        $this->layer = 'parcelas_sigef';
        $this->timeout = config('geoserver.timeout', 30);
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

    public function getParcelasPorMunicipio(string $uf, string $codigoIbge): array
    {
        try {
            Log::debug('Buscando parcelas SIGEF', [
                'estado' => $uf,
                'municipio' => $codigoIbge
            ]);

            // Validação do código IBGE
            if (!preg_match('/^\d{7}$/', $codigoIbge)) {
                Log::error('Código IBGE inválido', [
                    'codigo' => $codigoIbge
                ]);
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Código do município inválido. O código deve conter 7 dígitos.'
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
            $params = [
                'CQL_FILTER' => "municipio_ibge = '{$codigoIbge}'",
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'maxFeatures' => self::MAX_FEATURES
            ];

            Log::debug('Parâmetros da requisição SIGEF', [
                'typeName' => $typeName,
                'params' => $params
            ]);

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
                'Accept' => 'application/json',
                'User-Agent' => 'ODSGEO/1.0 (SIGEF Integration)',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache'
            ])
            ->withCookies($this->cookies, '.incra.gov.br')
            ->retry(2, 3000)
            ->get($this->baseUrl, $params);

            if (!$response->successful()) {
                Log::error('Erro na requisição WFS', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Erro ao consultar o serviço SIGEF. Tente novamente mais tarde.'
                ];
            }

            $data = $response->json();
            
            if (empty($data['features'])) {
                return [
                    'success' => true,
                    'has_data' => false,
                    'data' => $response->body()
                ];
            }

            return [
                'success' => true,
                'has_data' => true,
                'data' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas SIGEF', [
                'error' => $e->getMessage(),
                'estado' => $uf,
                'municipio' => $codigoIbge,
                'type' => get_class($e)
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Ocorreu um erro ao buscar as parcelas. Tente novamente mais tarde.'
            ];
        }
    }

    protected function establishSession(): void
    {
        $this->abrirSessao();
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

    public function buscarParcelasPorCoordenada(float $lat, float $lon, float $raio, string $uf): array
    {
        try {
            Log::debug('Buscando parcelas SIGEF por coordenada', [
                'lat' => $lat,
                'lon' => $lon,
                'raio' => $raio,
                'uf' => $uf
            ]);

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
                'maxFeatures' => self::MAX_FEATURES
            ];

            Log::debug('Parâmetros da requisição SIGEF', [
                'typeName' => $typeName,
                'params' => $params
            ]);

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
                'Accept' => 'application/json',
                'User-Agent' => 'ODSGEO/1.0 (SIGEF Integration)',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache'
            ])
            ->withCookies($this->cookies, '.incra.gov.br')
            ->retry(2, 3000)
            ->get($this->baseUrl, $params);

            if (!$response->successful()) {
                Log::error('Erro na requisição WFS', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Erro ao consultar o serviço SIGEF. Tente novamente mais tarde.'
                ];
            }

            $data = $response->json();
            
            if (empty($data['features'])) {
                return [
                    'success' => true,
                    'has_data' => false,
                    'data' => $response->body(),
                    'bbox' => $bbox
                ];
            }

            return [
                'success' => true,
                'has_data' => true,
                'data' => $response->body(),
                'bbox' => $bbox
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas SIGEF por coordenada', [
                'error' => $e->getMessage(),
                'lat' => $lat,
                'lon' => $lon,
                'raio' => $raio,
                'uf' => $uf,
                'type' => get_class($e)
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Ocorreu um erro ao buscar as parcelas. Tente novamente mais tarde.'
            ];
        }
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

    public function getParcelasByCodigo(?string $codigoImovel = null, ?string $matriculaSigef = null): array
    {
        try {
            Log::debug('Buscando parcela SIGEF por código', [
                'codigo_imovel' => $codigoImovel,
                'matricula_sigef' => $matriculaSigef
            ]);

            if (!$codigoImovel && !$matriculaSigef) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Informe o código do imóvel ou a matrícula SIGEF'
                ];
            }

            if (!$this->abrirSessao()) {
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Não foi possível estabelecer conexão com o SIGEF'
                ];
            }

            $cqlFilter = [];
            if ($codigoImovel) {
                $cqlFilter[] = "codigo_imovel = '{$codigoImovel}'";
            }
            if ($matriculaSigef) {
                $cqlFilter[] = "matricula_sigef = '{$matriculaSigef}'";
            }

            $params = [
                'CQL_FILTER' => implode(' OR ', $cqlFilter),
                'outputFormat' => 'application/json',
                'srsName' => 'EPSG:4326',
                'maxFeatures' => self::MAX_FEATURES
            ];

            $startTime = microtime(true);
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
                'Accept' => 'application/json',
                'User-Agent' => 'ODSGEO/1.0 (SIGEF Integration)',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache'
            ])
            ->withCookies($this->cookies, '.incra.gov.br')
            ->retry(2, 3000)
            ->get($this->baseUrl, $params);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            Log::info('Resposta da busca por código SIGEF', [
                'status' => $response->status(),
                'response_time' => $responseTime,
                'params' => $params
            ]);

            if (!$response->successful()) {
                Log::error('Erro na requisição WFS por código', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'response_time' => $responseTime
                ]);
                return [
                    'success' => false,
                    'has_data' => false,
                    'error' => 'Erro ao consultar o serviço SIGEF'
                ];
            }

            $data = $response->json();
            
            if (empty($data['features'])) {
                return [
                    'success' => true,
                    'has_data' => false,
                    'data' => $response->body()
                ];
            }

            return [
                'success' => true,
                'has_data' => true,
                'data' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcela SIGEF por código', [
                'error' => $e->getMessage(),
                'codigo_imovel' => $codigoImovel,
                'matricula_sigef' => $matriculaSigef,
                'type' => get_class($e)
            ]);

            return [
                'success' => false,
                'has_data' => false,
                'error' => 'Ocorreu um erro ao buscar a parcela'
            ];
        }
    }
} 