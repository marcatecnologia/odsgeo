<?php

namespace App\Services;

use App\Models\PlanilhaOds;
use App\Models\Segmento;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class SegmentoImportService
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
            $requiredHeaders = ['vertice_inicial', 'vertice_final', 'azimute', 'distancia'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            
            if (!empty($missingHeaders)) {
                throw new \Exception('Cabeçalhos obrigatórios ausentes: ' . implode(', ', $missingHeaders));
            }
            
            // Iniciar transação
            \DB::beginTransaction();
            
            // Obter última ordem
            $ultimaOrdem = $planilha->segmentos()->max('ordem') ?? 0;
            
            // Validar e importar registros
            $registros = $csv->getRecords();
            $totalRegistros = 0;
            $erros = [];

            foreach ($registros as $index => $record) {
                $linha = $index + 2; // +2 porque o índice começa em 0 e há o cabeçalho
                $totalRegistros++;

                // Validar campos obrigatórios
                if (empty($record['vertice_inicial'])) {
                    $erros[] = "Linha {$linha}: Vértice inicial é obrigatório";
                    continue;
                }

                if (empty($record['vertice_final'])) {
                    $erros[] = "Linha {$linha}: Vértice final é obrigatório";
                    continue;
                }

                if (!is_numeric($record['azimute'])) {
                    $erros[] = "Linha {$linha}: Azimute deve ser um número";
                    continue;
                }

                if (!is_numeric($record['distancia'])) {
                    $erros[] = "Linha {$linha}: Distância deve ser um número";
                    continue;
                }

                // Validar azimute
                $azimute = floatval($record['azimute']);
                if ($azimute < 0 || $azimute > 360) {
                    $erros[] = "Linha {$linha}: Azimute deve estar entre 0 e 360 graus";
                    continue;
                }

                // Validar distância
                $distancia = floatval($record['distancia']);
                if ($distancia <= 0) {
                    $erros[] = "Linha {$linha}: Distância deve ser maior que zero";
                    continue;
                }

                // Buscar vértices pelo nome
                $verticeInicial = $planilha->vertices()->where('nome_ponto', $record['vertice_inicial'])->first();
                $verticeFinal = $planilha->vertices()->where('nome_ponto', $record['vertice_final'])->first();
                
                if (!$verticeInicial) {
                    $erros[] = "Linha {$linha}: Vértice inicial '{$record['vertice_inicial']}' não encontrado";
                    continue;
                }

                if (!$verticeFinal) {
                    $erros[] = "Linha {$linha}: Vértice final '{$record['vertice_final']}' não encontrado";
                    continue;
                }

                // Validar segmento duplicado
                $segmentoExistente = $planilha->segmentos()
                    ->where(function ($query) use ($verticeInicial, $verticeFinal) {
                        $query->where(function ($q) use ($verticeInicial, $verticeFinal) {
                            $q->where('vertice_inicial_id', $verticeInicial->id)
                                ->where('vertice_final_id', $verticeFinal->id);
                        })->orWhere(function ($q) use ($verticeInicial, $verticeFinal) {
                            $q->where('vertice_inicial_id', $verticeFinal->id)
                                ->where('vertice_final_id', $verticeInicial->id);
                        });
                    })
                    ->first();

                if ($segmentoExistente) {
                    $erros[] = "Linha {$linha}: Já existe um segmento entre os vértices '{$record['vertice_inicial']}' e '{$record['vertice_final']}'";
                    continue;
                }

                $ultimaOrdem++;
                
                Segmento::create([
                    'planilha_ods_id' => $planilha->id,
                    'vertice_inicial_id' => $verticeInicial->id,
                    'vertice_final_id' => $verticeFinal->id,
                    'azimute' => $azimute,
                    'distancia' => $distancia,
                    'confrontante' => $record['confrontante'] ?? null,
                    'tipo_limite' => $record['tipo_limite'] ?? null,
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