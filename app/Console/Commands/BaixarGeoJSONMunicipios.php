<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaixarGeoJSONMunicipios extends Command
{
    protected $signature = 'geojson:baixar-municipios';
    protected $description = 'Baixa o GeoJSON de municípios do IBGE';

    protected $urls = [
        'https://servicodados.ibge.gov.br/api/v3/malhas/municipios?formato=application/vnd.geo+json',
        'https://servicodados.ibge.gov.br/api/v2/malhas/municipios?formato=application/vnd.geo+json',
        'https://servicodados.ibge.gov.br/api/v1/malhas/municipios?formato=application/vnd.geo+json'
    ];

    public function handle()
    {
        $this->info('Iniciando download do GeoJSON de municípios...');

        try {
            // Cria diretório se não existir
            $dir = base_path('database/geojson');
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $success = false;
            $lastError = null;

            // Tenta cada URL até conseguir
            foreach ($this->urls as $url) {
                try {
                    $this->info("Tentando baixar de: {$url}");
                    $response = Http::timeout(300)->get($url);

                    if ($response->successful()) {
                        $geojson = $response->json();
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $success = true;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    continue;
                }
            }

            if (!$success) {
                throw new \Exception("Não foi possível baixar o arquivo de nenhuma das URLs. Último erro: {$lastError}");
            }

            // Adiciona UF às propriedades
            foreach ($geojson['features'] as &$feature) {
                if (isset($feature['properties']['codigo_ibge'])) {
                    $codigo = $feature['properties']['codigo_ibge'];
                    $uf = substr($codigo, 0, 2);
                    $feature['properties']['uf'] = $uf;
                }
            }

            // Salva arquivo
            $file = "{$dir}/municipios.geojson";
            file_put_contents($file, json_encode($geojson, JSON_UNESCAPED_UNICODE));

            $this->info('Download concluído com sucesso!');
            $this->info("Arquivo salvo em: {$file}");

        } catch (\Exception $e) {
            $this->error('Erro: ' . $e->getMessage());
            Log::error('Erro ao baixar GeoJSON', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 