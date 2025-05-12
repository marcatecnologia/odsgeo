<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SigefWfsService
{
    private $client;
    private $baseUrl;
    private $maxRetries = 3;
    private $retryDelays = [5, 10, 15]; // Delays em segundos
    private $connectTimeout = 5;
    private $timeout = 10;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->baseUrl = 'https://acervofundiario.incra.gov.br/i3geo/ogc.php';
    }

    public function getCapabilities(): array
    {
        $cacheKey = 'sigef_wfs_capabilities';
        
        // Tenta recuperar do cache primeiro
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $startTime = microtime(true);
                
                $response = $this->client->get($this->baseUrl, [
                    'query' => [
                        'service' => 'WFS',
                        'request' => 'GetCapabilities',
                        'version' => '1.0.0'
                    ]
                ]);

                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2);

                if ($response->getStatusCode() === 200) {
                    $data = [
                        'success' => true,
                        'data' => $response->getBody()->getContents(),
                        'response_time' => $responseTime,
                        'headers' => $response->getHeaders()
                    ];

                    // Cache por 1 hora
                    Cache::put($cacheKey, $data, 3600);
                    
                    return $data;
                }

                throw new \Exception('Resposta não esperada: ' . $response->getStatusCode());

            } catch (GuzzleException $e) {
                $lastException = $e;
                $attempt++;

                $this->logError($e, $attempt);

                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelays[$attempt - 1];
                    sleep($delay);
                    continue;
                }
            }
        }

        // Se chegou aqui, todas as tentativas falharam
        return [
            'success' => false,
            'error' => 'Serviço temporariamente indisponível',
            'details' => [
                'message' => $lastException->getMessage(),
                'code' => $lastException->getCode(),
                'attempts' => $attempt,
                'last_exception' => get_class($lastException)
            ]
        ];
    }

    private function logError(GuzzleException $e, int $attempt): void
    {
        $context = [
            'timestamp' => now()->toIso8601String(),
            'url' => $this->baseUrl,
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'connect_timeout' => $this->connectTimeout,
            'timeout' => $this->timeout,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ];

        if ($e instanceof ConnectException) {
            $context['error_type'] = 'connection_timeout';
        } elseif ($e instanceof RequestException) {
            $context['error_type'] = 'request_error';
        } elseif ($e instanceof ServerException) {
            $context['error_type'] = 'server_error';
        }

        Log::error('Erro na requisição WFS', $context);
    }

    public function getFeature(string $typeName, array $params = []): array
    {
        $cacheKey = "sigef_wfs_feature_{$typeName}_" . md5(json_encode($params));
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $startTime = microtime(true);
                
                $response = $this->client->get($this->baseUrl, [
                    'query' => array_merge([
                        'service' => 'WFS',
                        'request' => 'GetFeature',
                        'version' => '1.0.0',
                        'typeName' => $typeName
                    ], $params)
                ]);

                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2);

                if ($response->getStatusCode() === 200) {
                    $data = [
                        'success' => true,
                        'data' => $response->getBody()->getContents(),
                        'response_time' => $responseTime,
                        'headers' => $response->getHeaders()
                    ];

                    // Cache por 5 minutos
                    Cache::put($cacheKey, $data, 300);
                    
                    return $data;
                }

                throw new \Exception('Resposta não esperada: ' . $response->getStatusCode());

            } catch (GuzzleException $e) {
                $lastException = $e;
                $attempt++;

                $this->logError($e, $attempt);

                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelays[$attempt - 1];
                    sleep($delay);
                    continue;
                }
            }
        }

        return [
            'success' => false,
            'error' => 'Serviço temporariamente indisponível',
            'details' => [
                'message' => $lastException->getMessage(),
                'code' => $lastException->getCode(),
                'attempts' => $attempt,
                'last_exception' => get_class($lastException)
            ]
        ];
    }
} 