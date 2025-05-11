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
            
            // Importar registros
            foreach ($csv->getRecords() as $record) {
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
            
            \DB::commit();
            
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
} 