<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AtualizarMunicipios extends Command
{
    protected $signature = 'municipios:atualizar {uf}';
    protected $description = 'Atualiza a base local de municípios de um estado (UF) com centroides';

    public function handle()
    {
        $uf = strtoupper($this->argument('uf'));
        $dir = base_path('database/municipios');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = "$dir/{$uf}.json";
        $this->info("Baixando municípios do estado {$uf}...");
        $response = Http::get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios");
        if ($response->ok()) {
            $arr = $response->json();
            $municipios = [];
            foreach ($arr as $m) {
                $centroide = null;
                $centroideResp = Http::get("https://servicodados.ibge.gov.br/api/v2/municipios/{$m['id']}");
                if ($centroideResp->ok()) {
                    $data = $centroideResp->json();
                    if (isset($data[0]['centroide'])) {
                        $centroide = $data[0]['centroide'];
                    }
                }
                $municipios[] = [
                    'codigo_ibge' => $m['id'],
                    'nome' => $m['nome'],
                    'lat' => $centroide['latitude'] ?? null,
                    'lng' => $centroide['longitude'] ?? null,
                ];
                $this->line("- {$m['nome']} ({$m['id']})");
            }
            file_put_contents($file, json_encode($municipios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $this->info("Arquivo salvo em {$file} ({$uf})");
        } else {
            $this->error('Erro ao baixar municípios do IBGE.');
        }
    }
} 