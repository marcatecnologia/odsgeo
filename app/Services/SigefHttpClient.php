<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
    private $baseUrl;
    private $client;
    private $maxRetries = 3;
    private $retryDelay = 1000; // 1 segundo

    public function __construct()
    {
        $this->baseUrl = 'http://acervofundiario.incra.gov.br/i3geo/ogc.php';
        
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

    public function buscarParcelas(string $estado, string $municipio, int $maxFeatures = 100)
    {
        $cacheKey = "sigef_parcelas_{$estado}_{$municipio}";
        $failureKey = "sigef_failure_{$estado}_{$municipio}";

        // Verifica se houve falha recente
        if ($failure = Cache::get($failureKey)) {
            if (now()->diffInSeconds($failure['timestamp']) < 60) {
                throw new \Exception('Serviço temporariamente indisponível. Por favor, tente novamente em alguns instantes.');
            }
            Cache::forget($failureKey);
        }

        try {
            $startTime = microtime(true);
            
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'tema' => 'parcelageo_' . strtolower($estado),
                    'request' => 'GetFeature',
                    'service' => 'WFS',
                    'version' => '1.0.0',
                    'typeName' => 'parcelageo_' . strtolower($estado),
                    'outputFormat' => 'application/json',
                    'CQL_FILTER' => "municipio ILIKE '%{$municipio}%'",
                    'maxFeatures' => $maxFeatures
                ]
            ]);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                // Cache do resultado por 5 minutos
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                
                // Log de sucesso
                Log::info('Requisição WFS bem-sucedida', [
                    'estado' => $estado,
                    'municipio' => $municipio,
                    'response_time' => $responseTime . 'ms',
                    'status_code' => $response->getStatusCode(),
                    'content_length' => strlen($response->getBody())
                ]);

                return $data;
            }

            throw new \Exception('Erro ao buscar parcelas: ' . $response->getStatusCode());
        } catch (ConnectException $e) {
            $this->handleFailure($failureKey, $e, [
                'estado' => $estado,
                'municipio' => $municipio,
                'error_type' => 'connection_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            throw new \Exception('Não foi possível conectar ao serviço SIGEF. Por favor, tente novamente mais tarde.');
        } catch (ServerException $e) {
            $this->handleFailure($failureKey, $e, [
                'estado' => $estado,
                'municipio' => $municipio,
                'error_type' => 'server_error',
                'status_code' => $e->getResponse()->getStatusCode(),
                'error_message' => $e->getMessage()
            ]);
            
            throw new \Exception('O serviço SIGEF está temporariamente indisponível. Por favor, tente novamente mais tarde.');
        } catch (RequestException $e) {
            $this->handleFailure($failureKey, $e, [
                'estado' => $estado,
                'municipio' => $municipio,
                'error_type' => 'request_error',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            
            throw new \Exception('Erro ao processar a requisição. Por favor, tente novamente mais tarde.');
        } catch (\Exception $e) {
            Log::error('Erro não tratado na requisição WFS', [
                'estado' => $estado,
                'municipio' => $municipio,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.');
        }
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