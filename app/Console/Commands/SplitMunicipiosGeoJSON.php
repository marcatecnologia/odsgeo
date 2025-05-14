<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SplitMunicipiosGeoJSON extends Command
{
    protected $signature = 'geojson:split-municipios';
    protected $description = 'Divide o arquivo GeoJSON de municípios em arquivos separados por UF';

    public function handle()
    {
        $this->info('Iniciando divisão do arquivo GeoJSON de municípios...');
        
        $sourceFile = public_path('geojson/limites/municipios.geojson');
        $outputDir = storage_path('app/geojson/municipios');
        
        if (!File::exists($sourceFile)) {
            $this->error('Arquivo fonte não encontrado!');
            return 1;
        }

        $this->info("Arquivo fonte encontrado: {$sourceFile}");

        // Criar diretório de saída se não existir
        if (!File::exists($outputDir)) {
            try {
                File::makeDirectory($outputDir, 0777, true);
                $this->info("Diretório criado: {$outputDir}");
            } catch (\Exception $e) {
                $this->error("Erro ao criar diretório: {$e->getMessage()}");
                $this->info("Tentando criar com permissões alternativas...");
                
                // Tenta criar o diretório com permissões mais permissivas
                exec("mkdir -p {$outputDir}");
                exec("chmod -R 777 {$outputDir}");
                
                if (!File::exists($outputDir)) {
                    $this->error("Não foi possível criar o diretório. Por favor, execute os seguintes comandos manualmente:");
                    $this->info("mkdir -p {$outputDir}");
                    $this->info("chmod -R 777 {$outputDir}");
                    return 1;
                }
            }
        }

        // Ler o arquivo em chunks para evitar problemas de memória
        $this->info("Iniciando leitura do arquivo...");
        $handle = fopen($sourceFile, 'r');
        
        if (!$handle) {
            $this->error("Não foi possível abrir o arquivo para leitura!");
            return 1;
        }

        $header = '';
        $features = [];
        $ufs = [];
        $lineCount = 0;
        $featureCount = 0;

        // Ler o cabeçalho do GeoJSON
        while (($line = fgets($handle)) !== false) {
            $lineCount++;
            if (strpos($line, '"features":') !== false) {
                $header = substr($line, 0, strpos($line, '"features":') + 11);
                $this->info("Cabeçalho encontrado na linha {$lineCount}");
                break;
            }
        }

        // Processar features
        $this->info("Iniciando processamento das features...");
        while (($line = fgets($handle)) !== false) {
            $lineCount++;
            if (strpos($line, '"UF":') !== false) {
                preg_match('/"UF":"([^"]+)"/', $line, $matches);
                if (isset($matches[1])) {
                    $uf = $matches[1];
                    $ufs[$uf][] = $line;
                    $featureCount++;
                    
                    if ($featureCount % 100 === 0) {
                        $this->info("Processadas {$featureCount} features...");
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Total de features processadas: {$featureCount}");

        // Criar arquivos separados por UF
        $this->info("Iniciando criação dos arquivos por UF...");
        foreach ($ufs as $uf => $features) {
            $outputFile = $outputDir . "/municipios_{$uf}.geojson";
            $content = '{
    "type": "FeatureCollection",
    "features": [' . implode(',', $features) . ']
}';
            
            try {
                File::put($outputFile, $content);
                chmod($outputFile, 0666); // Permissões mais permissivas para escrita
                $this->info("Arquivo criado para UF {$uf}: {$outputFile}");
            } catch (\Exception $e) {
                $this->error("Erro ao criar arquivo para UF {$uf}: {$e->getMessage()}");
                continue;
            }
        }

        $this->info('Processo concluído com sucesso!');
        $this->info('Os arquivos foram salvos em: ' . $outputDir);
        $this->info('Para acessar os arquivos via web, execute: php artisan storage:link');
        return 0;
    }
} 