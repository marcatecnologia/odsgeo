<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGeoServerConnection extends Command
{
    protected $signature = 'geoserver:test-connection';
    protected $description = 'Testa a conexão com o GeoServer';

    public function handle()
    {
        $baseUrl = config('geoserver.url');
        $username = config('geoserver.username');
        $password = config('geoserver.password');
        $workspace = config('geoserver.workspace');

        $this->info("Testando conexão com o GeoServer...");
        $this->info("URL: {$baseUrl}");
        $this->info("Workspace: {$workspace}");

        try {
            // Testa a conexão básica
            $this->info("\nTestando conexão básica...");
            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->get("{$baseUrl}/rest/about/version");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['about']['resource'][0]['version'])) {
                    $this->info("✅ Conexão básica estabelecida com sucesso!");
                    $this->info("Versão do GeoServer: " . $data['about']['resource'][0]['version']);
                } else {
                    $this->warn("⚠️ Resposta do GeoServer não contém informações de versão");
                    $this->info("Resposta recebida: " . json_encode($data, JSON_PRETTY_PRINT));
                }
            } else {
                $this->warn("⚠️ Não foi possível obter a versão do GeoServer");
                $this->info("Status: " . $response->status());
                $this->info("Resposta: " . $response->body());
            }

            // Testa o acesso ao workspace
            $this->info("\nTestando acesso ao workspace...");
            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->get("{$baseUrl}/rest/workspaces/{$workspace}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['workspace']['name'])) {
                    $this->info("✅ Workspace '{$workspace}' encontrado!");
                } else {
                    $this->error("❌ Resposta inválida do GeoServer");
                    $this->error("Resposta recebida: " . json_encode($data, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ Erro ao acessar o workspace '{$workspace}'");
                $this->error("Status: " . $response->status());
                $this->error("Resposta: " . $response->body());
            }

            // Testa o acesso à camada de parcelas
            $layer = config('geoserver.layer');
            $this->info("\nTestando acesso à camada...");
            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->get("{$baseUrl}/rest/layers/{$workspace}:{$layer}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['layer']['name'])) {
                    $this->info("✅ Camada '{$layer}' encontrada!");
                } else {
                    $this->error("❌ Resposta inválida do GeoServer");
                    $this->error("Resposta recebida: " . json_encode($data, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ Erro ao acessar a camada '{$layer}'");
                $this->error("Status: " . $response->status());
                $this->error("Resposta: " . $response->body());
            }

            // Testa o serviço WFS
            $this->info("\nTestando serviço WFS...");
            $params = [
                'service' => 'WFS',
                'version' => '2.0.0',
                'request' => 'GetCapabilities',
                'typeName' => "{$workspace}:{$layer}"
            ];

            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->get("{$baseUrl}/wfs", $params);

            if ($response->successful()) {
                $this->info("✅ Serviço WFS funcionando!");
            } else {
                $this->error("❌ Erro ao acessar o serviço WFS");
                $this->error("Status: " . $response->status());
                $this->error("Resposta: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error("\n❌ Erro ao testar conexão:");
            $this->error($e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());
            
            Log::error('Erro ao testar conexão com GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'baseUrl' => $baseUrl,
                'workspace' => $workspace
            ]);
        }
    }
} 