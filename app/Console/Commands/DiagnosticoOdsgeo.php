<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class DiagnosticoOdsgeo extends Command
{
    protected $signature = 'odsgeo:diagnostico {--base-url= : Base URL para testar as rotas web e API}';
    protected $description = 'Executa diagnóstico das conexões e rotas principais do sistema ODSGEO';

    public function handle()
    {
        $baseUrl = $this->option('base-url') ?: config('app.url', 'http://localhost:8000');
        $this->info('Iniciando diagnóstico do sistema ODSGEO...');
        $this->testarBanco();
        $this->testarGeoServer();
        $this->testarRotas($baseUrl);
        $this->info('Diagnóstico concluído!');
    }

    protected function testarBanco()
    {
        $this->info("\n[1] Testando conexão com o banco de dados...");
        try {
            DB::connection()->getPdo();
            $this->info('✅ Banco de dados conectado com sucesso!');
        } catch (\Exception $e) {
            $this->error('❌ Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    protected function testarGeoServer()
    {
        $this->info("\n[2] Testando conexão com o GeoServer...");
        $baseUrl = config('geoserver.url');
        $username = config('geoserver.username');
        $password = config('geoserver.password');
        $workspace = config('geoserver.workspace');
        $layer = config('geoserver.layer');

        // Testa versão
        $response = Http::withBasicAuth($username, $password)
            ->timeout(15)
            ->get("{$baseUrl}/rest/about/version");
        if ($response->successful()) {
            $data = $response->json();
            $versao = $data['about']['resource'][0]['version'] ?? 'desconhecida';
            $this->info("✅ GeoServer acessível! Versão: {$versao}");
        } else {
            $this->warn('⚠️ Não foi possível obter a versão do GeoServer. Status: ' . $response->status());
        }

        // Testa workspace
        $response = Http::withBasicAuth($username, $password)
            ->timeout(15)
            ->get("{$baseUrl}/rest/workspaces/{$workspace}");
        if ($response->successful()) {
            $this->info("✅ Workspace '{$workspace}' acessível!");
        } else {
            $this->error("❌ Workspace '{$workspace}' não acessível. Status: " . $response->status());
        }

        // Testa camada
        $response = Http::withBasicAuth($username, $password)
            ->timeout(15)
            ->get("{$baseUrl}/rest/layers/{$workspace}:{$layer}");
        if ($response->successful()) {
            $this->info("✅ Camada '{$layer}' acessível!");
        } else {
            $this->error("❌ Camada '{$layer}' não acessível. Status: " . $response->status());
        }

        // Testa WFS
        $params = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetCapabilities',
            'typeName' => "{$workspace}:{$layer}"
        ];
        $response = Http::withBasicAuth($username, $password)
            ->timeout(15)
            ->get("{$baseUrl}/wfs", $params);
        if ($response->successful()) {
            $this->info("✅ Serviço WFS funcionando!");
        } else {
            $this->error("❌ Serviço WFS não acessível. Status: " . $response->status());
        }
    }

    protected function testarRotas($baseUrl)
    {
        $this->info("\n[3] Testando rotas principais do sistema...");
        $rotas = [
            'Página Parcelas SIGEF' => rtrim($baseUrl, '/') . '/admin/parcelas-sigef',
            'API Parcelas (exemplo)' => rtrim($baseUrl, '/') . '/api/parcelas?municipio=1',
            'Página inicial' => rtrim($baseUrl, '/') . '/',
        ];
        foreach ($rotas as $nome => $rota) {
            try {
                $response = Http::timeout(10)->get($rota);
                if ($response->successful()) {
                    $this->info("✅ {$nome} acessível! [{$rota}]");
                } else {
                    $this->warn("⚠️ {$nome} retornou status {$response->status()} [{$rota}]");
                }
            } catch (\Exception $e) {
                $this->error("❌ Erro ao acessar {$nome}: " . $e->getMessage());
            }
        }
    }
} 