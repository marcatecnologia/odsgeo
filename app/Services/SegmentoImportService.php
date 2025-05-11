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
            
            // Importar registros
            foreach ($csv->getRecords() as $record) {
                $ultimaOrdem++;
                
                // Buscar vértices pelo nome
                $verticeInicial = $planilha->vertices()->where('nome_ponto', $record['vertice_inicial'])->first();
                $verticeFinal = $planilha->vertices()->where('nome_ponto', $record['vertice_final'])->first();
                
                if (!$verticeInicial || !$verticeFinal) {
                    throw new \Exception('Vértice não encontrado: ' . 
                        (!$verticeInicial ? $record['vertice_inicial'] : $record['vertice_final']));
                }
                
                Segmento::create([
                    'planilha_ods_id' => $planilha->id,
                    'vertice_inicial_id' => $verticeInicial->id,
                    'vertice_final_id' => $verticeFinal->id,
                    'azimute' => $record['azimute'],
                    'distancia' => $record['distancia'],
                    'confrontante' => $record['confrontante'] ?? null,
                    'tipo_limite' => $record['tipo_limite'] ?? null,
                    'ordem' => $ultimaOrdem,
                ]);
            }
            
            \DB::commit();
            
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
} 