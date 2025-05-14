<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AtualizarMunicipios extends Command
{
    protected $signature = 'municipios:atualizar';
    protected $description = 'Atualiza a base de municípios de todos os estados do Brasil';
    
    protected $maxRetries = 3;
    protected $retryDelay = 5; // segundos

    public function handle()
    {
        $estados = [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
            'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
            'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
            'SE' => 'Sergipe', 'TO' => 'Tocantins'
        ];

        $dir = base_path('database/municipios');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $bar = $this->output->createProgressBar(count($estados));
        $bar->start();

        foreach ($estados as $uf => $nome) {
            $this->atualizarEstado($uf, $nome, $dir);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Atualização de municípios concluída!');
    }

    protected function atualizarEstado($uf, $nome, $dir)
    {
        $this->info("\nAtualizando municípios do {$nome} ({$uf})...");
        
        try {
            // Primeira tentativa de buscar municípios
            $response = $this->fazerRequisicaoComRetry(
                "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios"
            );
            
            if (!$response) {
                $this->error(" ✗ {$nome}: Não foi possível conectar à API do IBGE após várias tentativas");
                return;
            }

            $arr = $response->json();
            $municipios = [];
            $total = count($arr);
            $progress = $this->output->createProgressBar($total);
            $progress->start();
            
            foreach ($arr as $m) {
                // Buscar centroide (API v2)
                $centroide = null;
                $centroideResp = $this->fazerRequisicaoComRetry(
                    "https://servicodados.ibge.gov.br/api/v2/municipios/{$m['id']}"
                );
                
                if ($centroideResp && $centroideResp->ok()) {
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
                
                $progress->advance();
                sleep(1); // Delay de 1 segundo entre requisições
            }
            
            $progress->finish();
            $this->newLine();
            
            $file = "{$dir}/{$uf}.json";
            file_put_contents($file, json_encode($municipios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            
            $this->info(" ✓ {$nome}: " . count($municipios) . " municípios atualizados");
            
        } catch (\Exception $e) {
            $this->error(" ✗ {$nome}: " . $e->getMessage());
            Log::error("Erro ao atualizar municípios do {$nome}", [
                'uf' => $uf,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function fazerRequisicaoComRetry($url)
    {
        $attempt = 0;
        
        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout(30)->get($url);
                
                if ($response->ok()) {
                    return $response;
                }
                
                $this->warn("Tentativa " . ($attempt + 1) . " falhou. Status: " . $response->status());
                
            } catch (\Exception $e) {
                $this->warn("Tentativa " . ($attempt + 1) . " falhou: " . $e->getMessage());
            }
            
            $attempt++;
            
            if ($attempt < $this->maxRetries) {
                $this->info("Aguardando {$this->retryDelay} segundos antes da próxima tentativa...");
                sleep($this->retryDelay);
            }
        }
        
        return null;
    }
} 