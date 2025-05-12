<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Client;

class SigefDiagnosticService
{
    private $baseUrl;
    private $client;
    private $wfsService;

    public function __construct()
    {
        $this->baseUrl = 'https://acervofundiario.incra.gov.br/i3geo/ogc.php';
        $this->wfsService = new SigefWfsService();
        
        // Configuração mais robusta do cliente HTTP
        $this->client = new Client([
            'verify' => storage_path('certs/sigef.pem'),
            'timeout' => 10,
            'connect_timeout' => 5,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => storage_path('certs/sigef.pem'),
            ],
            'headers' => [
                'User-Agent' => 'ODSGeo/1.0',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function checkConnection(): array
    {
        $diagnostic = [
            'timestamp' => now()->toIso8601String(),
            'status' => 'error',
            'checks' => []
        ];

        // Verifica DNS
        $dnsCheck = $this->checkDNS();
        $diagnostic['checks']['dns'] = $dnsCheck;

        if (!$dnsCheck['success']) {
            return $diagnostic;
        }

        // Verifica SSL
        $sslCheck = $this->checkSSL();
        $diagnostic['checks']['ssl'] = $sslCheck;

        if (!$sslCheck['success']) {
            return $diagnostic;
        }

        // Verifica ping
        $pingCheck = $this->checkPing();
        $diagnostic['checks']['ping'] = $pingCheck;

        if (!$pingCheck['success']) {
            return $diagnostic;
        }

        // Verifica WFS
        $wfsCheck = $this->checkWFS();
        $diagnostic['checks']['wfs'] = $wfsCheck;

        if ($wfsCheck['success']) {
            $diagnostic['status'] = 'success';
        }

        return $diagnostic;
    }

    private function checkDNS(): array
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            $ip = gethostbyname($host);
            $result['success'] = ($ip !== $host);
            $result['message'] = $result['success'] ? 'DNS resolvido com sucesso' : 'Falha na resolução DNS';
            $result['details']['ip'] = $ip;
            $result['details']['expected_ip'] = '189.9.36.29';
            $result['details']['ip_match'] = ($ip === '189.9.36.29');
        } catch (\Exception $e) {
            $result['message'] = 'Erro ao resolver DNS: ' . $e->getMessage();
        }

        return $result;
    }

    private function checkSSL(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            // Verifica certificado SIGEF
            $sigefPath = storage_path('certs/sigef.pem');
            if (!file_exists($sigefPath)) {
                throw new \Exception('Arquivo de certificado SIGEF não encontrado');
            }

            if (!is_readable($sigefPath)) {
                throw new \Exception('Arquivo de certificado SIGEF não tem permissão de leitura');
            }

            $sigefInfo = openssl_x509_parse(file_get_contents($sigefPath));
            if (!$sigefInfo) {
                throw new \Exception('Certificado SIGEF inválido');
            }

            $result['success'] = true;
            $result['message'] = 'Certificado SSL válido';
            $result['details'] = [
                'sigef_issuer' => $sigefInfo['issuer']['O'] ?? 'Desconhecido',
                'sigef_valid_from' => date('Y-m-d H:i:s', $sigefInfo['validFrom_time_t']),
                'sigef_valid_to' => date('Y-m-d H:i:s', $sigefInfo['validTo_time_t']),
                'sigef_path' => $sigefPath,
                'permissions' => substr(sprintf('%o', fileperms($sigefPath)), -4)
            ];
        } catch (\Exception $e) {
            $result['message'] = 'Erro ao verificar certificado SSL: ' . $e->getMessage();
            $result['details']['error'] = $e->getMessage();
        }

        return $result;
    }

    private function checkPing(): array
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

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
            $result['success'] = $response->getStatusCode() === 200;
            $result['message'] = $result['success'] ? 'Ping realizado com sucesso' : 'Falha no ping';
            $result['details'] = [
                'response_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders()
            ];
        } catch (\Exception $e) {
            $result['message'] = 'Erro ao realizar ping: ' . $e->getMessage();
            if ($e instanceof ConnectException) {
                $result['details']['error_type'] = 'connection_timeout';
            }
            $result['details']['error'] = $e->getMessage();
        }

        return $result;
    }

    private function checkWFS(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            $wfsResponse = $this->wfsService->getCapabilities();

            if ($wfsResponse['success']) {
                $result['success'] = true;
                $result['message'] = 'Serviço WFS respondendo corretamente';
                $result['details'] = [
                    'response_time' => $wfsResponse['response_time'] . 'ms',
                    'headers' => $wfsResponse['headers']
                ];
            } else {
                $result['message'] = 'Serviço WFS retornou erro';
                $result['details'] = $wfsResponse['details'];
            }
        } catch (\Exception $e) {
            $result['message'] = 'Erro ao verificar serviço WFS: ' . $e->getMessage();
            if ($e instanceof ServerException) {
                $result['details']['error_type'] = 'server_error';
            } elseif ($e instanceof RequestException) {
                $result['details']['error_type'] = 'request_error';
            }
            $result['details']['error'] = $e->getMessage();
        }

        return $result;
    }
} 