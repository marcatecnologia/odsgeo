<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class SigefHttpClient
{
    protected $baseUrl;
    protected $sessionId;
    protected $maxRetries;
    protected $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('sigef.wfs.url');
        $this->maxRetries = config('sigef.retry.max_attempts', 3);
        $this->retryDelay = config('sigef.retry.delay', 1000);
        
        // Configuração do handler com retry
        $stack = HandlerStack::create();
        $stack->push($this->getRetryMiddleware());
        
        $this->client = new Client([
            'handler' => $stack,
            'verify' => storage_path('certs/cacert.pem'),
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    private function getRetryMiddleware()
    {
        return Middleware::retry(
            function (
                $retries,
                Request $request,
                ?Response $response = null,
                ?\Exception $exception = null
            ) {
                if ($retries >= $this->maxRetries) {
                    return false;
                }

                if ($exception instanceof ConnectException) {
                    return true;
                }

                if ($response) {
                    return $response->getStatusCode() >= 500;
                }

                return false;
            },
            function ($retries) {
                return $this->retryDelay * $retries;
            }
        );
    }

    public function getParcelasPorMunicipio($estado, $municipio, $page = 1)
    {
        $cacheKey = "sigef_parcelas_{$estado}_{$municipio}_{$page}";
        
        if (config('sigef.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        if (!$this->checkThrottle()) {
            return [
                'success' => false,
                'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.',
                'has_data' => false
            ];
        }

        try {
            $response = $this->client->get($this->baseUrl . '/parcelas', [
                'query' => [
                    'estado' => $estado,
                    'municipio' => $municipio,
                    'page' => $page,
                    'per_page' => config('sigef.pagination.per_page', 50)
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                // Simplifica geometrias se necessário
                if (config('sigef.geometry.simplify', true)) {
                    $data['features'] = $this->simplifyGeometries($data['features']);
                }

                $result = [
                    'success' => true,
                    'has_data' => !empty($data['features']),
                    'data' => json_encode([
                        'type' => 'FeatureCollection',
                        'features' => $data['features']
                    ]),
                    'pagination' => [
                        'total' => $data['total'] ?? 0,
                        'per_page' => config('sigef.pagination.per_page', 50),
                        'current_page' => $page,
                        'last_page' => ceil($data['total'] / config('sigef.pagination.per_page', 50))
                    ]
                ];

                if (config('sigef.cache.enabled', true)) {
                    Cache::put($cacheKey, $result, config('sigef.cache.ttl.parcelas', 300));
                }

                return $result;
            }

            throw new \Exception('Erro ao buscar parcelas: ' . $response->getStatusCode());
        } catch (ConnectException $e) {
            $this->handleFailure("sigef_failure_{$estado}_{$municipio}", $e, [
                'estado' => $estado,
                'municipio' => $municipio,
                'error_type' => 'connection_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Não foi possível conectar ao serviço SIGEF. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (ServerException $e) {
            $this->handleFailure("sigef_failure_{$estado}_{$municipio}", $e, [
                'estado' => $estado,
                'municipio' => $municipio,
                'error_type' => 'server_error',
                'status_code' => $e->getResponse()->getStatusCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'O serviço SIGEF está temporariamente indisponível. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (RequestException $e) {
            $this->handleFailure("sigef_failure_{$estado}_{$municipio}", $e, [
                'estado' => $estado,
                'municipio' => $municipio,
                'error_type' => 'request_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao processar a requisição. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (\Exception $e) {
            Log::error('Erro não tratado na requisição WFS', [
                'estado' => $estado,
                'municipio' => $municipio,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        }
    }

    public function buscarParcelasPorCoordenada($latitude, $longitude, $raio, $estado)
    {
        $cacheKey = "sigef_parcelas_coord_{$latitude}_{$longitude}_{$raio}_{$estado}";
        
        if (config('sigef.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        if (!$this->checkThrottle()) {
            return [
                'success' => false,
                'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.',
                'has_data' => false
            ];
        }

        try {
            // Calcula o bounding box
            $bbox = $this->calculateBoundingBox($latitude, $longitude, $raio);

            $response = $this->client->get($this->baseUrl . '/parcelas/coordenada', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'raio' => $raio,
                    'estado' => $estado,
                    'bbox' => implode(',', $bbox)
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                // Simplifica geometrias se necessário
                if (config('sigef.geometry.simplify', true)) {
                    $data['features'] = $this->simplifyGeometries($data['features']);
                }

                $result = [
                    'success' => true,
                    'has_data' => !empty($data['features']),
                    'data' => json_encode([
                        'type' => 'FeatureCollection',
                        'features' => $data['features']
                    ]),
                    'bbox' => $bbox
                ];

                if (config('sigef.cache.enabled', true)) {
                    Cache::put($cacheKey, $result, config('sigef.cache.ttl.coordenadas', 300));
                }

                return $result;
            }

            throw new \Exception('Erro ao buscar parcelas: ' . $response->getStatusCode());
        } catch (ConnectException $e) {
            $this->handleFailure("sigef_failure_{$estado}_{$latitude}_{$longitude}_{$raio}", $e, [
                'estado' => $estado,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio,
                'error_type' => 'connection_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Não foi possível conectar ao serviço SIGEF. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (ServerException $e) {
            $this->handleFailure("sigef_failure_{$estado}_{$latitude}_{$longitude}_{$raio}", $e, [
                'estado' => $estado,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio,
                'error_type' => 'server_error',
                'status_code' => $e->getResponse()->getStatusCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'O serviço SIGEF está temporariamente indisponível. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (RequestException $e) {
            $this->handleFailure("sigef_failure_{$estado}_{$latitude}_{$longitude}_{$raio}", $e, [
                'estado' => $estado,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio,
                'error_type' => 'request_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao processar a requisição. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por coordenada', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raio' => $raio,
                'estado' => $estado
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao buscar parcelas: ' . $e->getMessage(),
                'has_data' => false
            ];
        }
    }

    public function getParcelasByCodigo($codigoImovel, $matriculaSigef)
    {
        $cacheKey = "sigef_parcelas_codigo_{$codigoImovel}_{$matriculaSigef}";
        
        if (config('sigef.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        if (!$this->checkThrottle()) {
            return [
                'success' => false,
                'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.',
                'has_data' => false
            ];
        }

        try {
            $response = $this->client->get($this->baseUrl . '/parcelas/codigo', [
                'query' => [
                    'codigo_imovel' => $codigoImovel,
                    'matricula_sigef' => $matriculaSigef
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                // Simplifica geometrias se necessário
                if (config('sigef.geometry.simplify', true)) {
                    $data['features'] = $this->simplifyGeometries($data['features']);
                }

                $result = [
                    'success' => true,
                    'has_data' => !empty($data['features']),
                    'data' => json_encode([
                        'type' => 'FeatureCollection',
                        'features' => $data['features']
                    ])
                ];

                if (config('sigef.cache.enabled', true)) {
                    Cache::put($cacheKey, $result, config('sigef.cache.ttl.codigo', 300));
                }

                return $result;
            }

            throw new \Exception('Erro ao buscar parcela: ' . $response->getStatusCode());
        } catch (ConnectException $e) {
            $this->handleFailure("sigef_failure_{$codigoImovel}_{$matriculaSigef}", $e, [
                'codigo_imovel' => $codigoImovel,
                'matricula_sigef' => $matriculaSigef,
                'error_type' => 'connection_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Não foi possível conectar ao serviço SIGEF. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (ServerException $e) {
            $this->handleFailure("sigef_failure_{$codigoImovel}_{$matriculaSigef}", $e, [
                'codigo_imovel' => $codigoImovel,
                'matricula_sigef' => $matriculaSigef,
                'error_type' => 'server_error',
                'status_code' => $e->getResponse()->getStatusCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'O serviço SIGEF está temporariamente indisponível. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (RequestException $e) {
            $this->handleFailure("sigef_failure_{$codigoImovel}_{$matriculaSigef}", $e, [
                'codigo_imovel' => $codigoImovel,
                'matricula_sigef' => $matriculaSigef,
                'error_type' => 'request_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao processar a requisição. Por favor, tente novamente mais tarde.',
                'has_data' => false
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcela por código', [
                'error' => $e->getMessage(),
                'codigo_imovel' => $codigoImovel,
                'matricula_sigef' => $matriculaSigef
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao buscar parcela: ' . $e->getMessage(),
                'has_data' => false
            ];
        }
    }

    protected function checkThrottle()
    {
        if (!config('sigef.throttle.enabled', true)) {
            return true;
        }

        $key = 'sigef_throttle_' . auth()->id();
        $maxAttempts = config('sigef.throttle.max_requests', 60);
        $decaySeconds = config('sigef.throttle.decay_seconds', 60);

        return RateLimiter::attempt($key, $maxAttempts, function() {
            return true;
        }, $decaySeconds);
    }

    protected function calculateBoundingBox($latitude, $longitude, $raio)
    {
        // Raio da Terra em metros
        $earthRadius = 6371000;

        // Converte raio de metros para graus
        $latDelta = ($raio / $earthRadius) * (180 / pi());
        $lonDelta = ($raio / $earthRadius) * (180 / pi()) / cos($latitude * pi() / 180);

        return [
            $longitude - $lonDelta, // minLon
            $latitude - $latDelta,  // minLat
            $longitude + $lonDelta, // maxLon
            $latitude + $latDelta   // maxLat
        ];
    }

    protected function simplifyGeometries($features)
    {
        $tolerance = config('sigef.geometry.tolerance', 0.0001);
        $maxPoints = config('sigef.geometry.max_points', 1000);

        foreach ($features as &$feature) {
            if (isset($feature['geometry']['coordinates'])) {
                $coords = $feature['geometry']['coordinates'][0][0];
                
                // Simplifica se tiver mais pontos que o máximo
                if (count($coords) > $maxPoints) {
                    $feature['geometry']['coordinates'][0][0] = $this->simplifyLineString($coords, $tolerance);
                }
            }
        }

        return $features;
    }

    protected function simplifyLineString($points, $tolerance)
    {
        if (count($points) <= 2) {
            return $points;
        }

        $maxDistance = 0;
        $index = 0;

        // Encontra o ponto mais distante da linha
        for ($i = 1; $i < count($points) - 1; $i++) {
            $distance = $this->pointToLineDistance(
                $points[$i],
                $points[0],
                $points[count($points) - 1]
            );

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $index = $i;
            }
        }

        // Se a distância máxima for maior que a tolerância, divide e simplifica
        if ($maxDistance > $tolerance) {
            $firstLine = array_slice($points, 0, $index + 1);
            $secondLine = array_slice($points, $index);

            $simplifiedFirst = $this->simplifyLineString($firstLine, $tolerance);
            $simplifiedSecond = $this->simplifyLineString($secondLine, $tolerance);

            // Remove o ponto duplicado
            array_pop($simplifiedFirst);

            return array_merge($simplifiedFirst, $simplifiedSecond);
        }

        // Se não, retorna apenas os pontos inicial e final
        return [$points[0], $points[count($points) - 1]];
    }

    protected function pointToLineDistance($point, $lineStart, $lineEnd)
    {
        $x = $point[0];
        $y = $point[1];
        $x1 = $lineStart[0];
        $y1 = $lineStart[1];
        $x2 = $lineEnd[0];
        $y2 = $lineEnd[1];

        $A = $x - $x1;
        $B = $y - $y1;
        $C = $x2 - $x1;
        $D = $y2 - $y1;

        $dot = $A * $C + $B * $D;
        $lenSq = $C * $C + $D * $D;
        $param = -1;

        if ($lenSq != 0) {
            $param = $dot / $lenSq;
        }

        $xx = 0;
        $yy = 0;

        if ($param < 0) {
            $xx = $x1;
            $yy = $y1;
        } else if ($param > 1) {
            $xx = $x2;
            $yy = $y2;
        } else {
            $xx = $x1 + $param * $C;
            $yy = $y1 + $param * $D;
        }

        $dx = $x - $xx;
        $dy = $y - $yy;

        return sqrt($dx * $dx + $dy * $dy);
    }

    private function handleFailure(string $failureKey, \Exception $e, array $context)
    {
        // Registra a falha no cache
        Cache::put($failureKey, [
            'timestamp' => now(),
            'error' => $e->getMessage(),
            'context' => $context
        ], now()->addMinutes(5));

        // Log detalhado do erro
        Log::error('Falha na requisição WFS', array_merge($context, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
    }
} 