<?php

namespace App\Services;

use App\Models\PlanilhaOds;
use App\Models\Vertice;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class VerticeImportService
{
    public function importar(PlanilhaOds $planilha, $arquivo)
    {
        try {
            // Validar extensão do arquivo
            if ($arquivo->getClientOriginalExtension() !== 'csv') {
                throw new \Exception('O arquivo deve estar no formato CSV.');
            }

            // Ler arquivo CSV
            $csv = Reader::createFromPath($arquivo->getPathname());
            $csv->setHeaderOffset(0);
            
            // Validar cabeçalhos
            $headers = $csv->getHeader();
            $requiredHeaders = ['nome_ponto', 'coordenada_x', 'coordenada_y'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            
            if (!empty($missingHeaders)) {
                throw new \Exception('Cabeçalhos obrigatórios ausentes: ' . implode(', ', $missingHeaders));
            }
            
            // Iniciar transação
            \DB::beginTransaction();
            
            // Obter última ordem
            $ultimaOrdem = $planilha->vertices()->max('ordem') ?? 0;
            
            // Validar e importar registros
            $registros = $csv->getRecords();
            $totalRegistros = 0;
            $erros = [];

            foreach ($registros as $index => $record) {
                $linha = $index + 2; // +2 porque o índice começa em 0 e há o cabeçalho
                $totalRegistros++;

                // Validar campos obrigatórios
                if (empty($record['nome_ponto'])) {
                    $erros[] = "Linha {$linha}: Nome do ponto é obrigatório";
                    continue;
                }

                if (!is_numeric($record['coordenada_x'])) {
                    $erros[] = "Linha {$linha}: Coordenada X deve ser um número";
                    continue;
                }

                if (!is_numeric($record['coordenada_y'])) {
                    $erros[] = "Linha {$linha}: Coordenada Y deve ser um número";
                    continue;
                }

                // Validar altitude se fornecida
                if (!empty($record['altitude']) && !is_numeric($record['altitude'])) {
                    $erros[] = "Linha {$linha}: Altitude deve ser um número";
                    continue;
                }

                // Validar nome do ponto único
                $verticeExistente = $planilha->vertices()
                    ->where('nome_ponto', $record['nome_ponto'])
                    ->first();

                if ($verticeExistente) {
                    $erros[] = "Linha {$linha}: Já existe um vértice com o nome '{$record['nome_ponto']}'";
                    continue;
                }

                $ultimaOrdem++;
                
                Vertice::create([
                    'planilha_ods_id' => $planilha->id,
                    'nome_ponto' => $record['nome_ponto'],
                    'coordenada_x' => $record['coordenada_x'],
                    'coordenada_y' => $record['coordenada_y'],
                    'altitude' => $record['altitude'] ?? null,
                    'tipo_marco' => $record['tipo_marco'] ?? null,
                    'codigo_sirgas' => $record['codigo_sirgas'] ?? null,
                    'ordem' => $ultimaOrdem,
                ]);
            }

            if (!empty($erros)) {
                throw new \Exception("Erros encontrados durante a importação:\n" . implode("\n", $erros));
            }

            if ($totalRegistros === 0) {
                throw new \Exception('Nenhum registro válido encontrado no arquivo.');
            }
            
            \DB::commit();
            
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
} 