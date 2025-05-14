<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DividirGeoJSONMunicipios extends Command
{
    protected $signature = 'geojson:dividir-municipios';
    protected $description = 'Divide o GeoJSON nacional de municípios em arquivos separados por UF';

    protected $estados = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    ];

    public function handle()
    {
        $this->info('Iniciando divisão do GeoJSON de municípios...');

        try {
            // Lê o arquivo GeoJSON nacional
            $jsonPath = base_path('public/geojson/limites/municipios.geojson');
            if (!file_exists($jsonPath)) {
                throw new \Exception("Arquivo não encontrado: {$jsonPath}. Por favor, certifique-se de que o arquivo existe em public/geojson/limites/municipios.geojson");
            }

            // Cria diretório se não existir
            $dir = base_path('database/geojson/municipios');
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $bar = $this->output->createProgressBar(count($this->estados));
            $bar->start();

            // Processa cada estado
            foreach ($this->estados as $uf => $nome) {
                $this->info("\nProcessando {$nome} ({$uf})...");
                
                // Extrai features do estado usando jq
                $tempFile = "{$dir}/temp_{$uf}.geojson";
                $command = "jq '.features[] | select(.properties.SIGLA_UF == \"{$uf}\")' {$jsonPath} > {$tempFile}";
                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    $this->warn(" ! {$nome}: Erro ao extrair features");
                    continue;
                }

                // Verifica se o arquivo tem conteúdo
                if (filesize($tempFile) > 0) {
                    // Cria o GeoJSON completo
                    $geojsonContent = "{\"type\":\"FeatureCollection\",\"features\":[" . file_get_contents($tempFile) . "]}";
                    $file = "{$dir}/municipios_{$uf}.geojson";
                    file_put_contents($file, $geojsonContent);

                    // Simplifica o GeoJSON
                    $this->simplificarGeoJSON($file);

                    // Conta o número de features
                    $featureCount = substr_count(file_get_contents($tempFile), '"type":"Feature"');
                    $this->info(" ✓ {$nome}: {$featureCount} municípios processados");
                } else {
                    $this->warn(" ! {$nome}: Nenhum município encontrado");
                }

                // Remove arquivo temporário
                unlink($tempFile);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('Divisão concluída com sucesso!');

        } catch (\Exception $e) {
            $this->error('Erro: ' . $e->getMessage());
            Log::error('Erro ao dividir GeoJSON', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function simplificarGeoJSON($file)
    {
        $output = [];
        $returnVar = 0;

        // Comando Mapshaper para simplificar e otimizar o GeoJSON
        $command = "mapshaper {$file} -simplify 10% -clean -o format=geojson force {$file}";
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Erro ao simplificar GeoJSON com Mapshaper: " . implode("\n", $output));
        }
    }
} 