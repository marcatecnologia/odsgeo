<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AtualizarMunicipiosRapido extends Command
{
    protected $signature = 'municipios:atualizar-rapido';
    protected $description = 'Atualiza a base de municípios usando arquivo local';

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
        $this->info('Iniciando atualização dos municípios...');
        
        try {
            // Lê o arquivo JSON local
            $jsonPath = base_path('EstadosEcidadesBrasil2020/estados-cidades.json');
            if (!file_exists($jsonPath)) {
                throw new \Exception("Arquivo não encontrado: {$jsonPath}");
            }

            $jsonContent = file_get_contents($jsonPath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
            }

            if (!isset($data['estados']) || !is_array($data['estados'])) {
                throw new \Exception('Formato de JSON inválido: chave "estados" não encontrada');
            }

            // Cria diretório se não existir
            $dir = base_path('database/municipios');
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $bar = $this->output->createProgressBar(count($this->estados));
            $bar->start();

            foreach ($data['estados'] as $estado) {
                $uf = $estado['sigla'];
                if (!isset($this->estados[$uf])) {
                    continue;
                }

                $this->info("\nProcessando {$this->estados[$uf]} ({$uf})...");

                $municipios = [];
                foreach ($estado['cidades'] as $nomeCidade) {
                    $municipios[] = [
                        'codigo_ibge' => null, // Será preenchido posteriormente
                        'nome' => $nomeCidade,
                        'lat' => null,
                        'lng' => null
                    ];
                }

                // Salva arquivo do estado
                $file = "{$dir}/{$uf}.json";
                file_put_contents($file, json_encode($municipios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
                
                $this->info(" ✓ {$this->estados[$uf]}: " . count($municipios) . " municípios atualizados");
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('Atualização concluída com sucesso!');

        } catch (\Exception $e) {
            $this->error('Erro: ' . $e->getMessage());
            Log::error('Erro ao atualizar municípios', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 