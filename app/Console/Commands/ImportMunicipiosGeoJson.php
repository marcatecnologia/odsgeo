<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportMunicipiosGeoJSON extends Command
{
    protected $signature = 'municipios:import-geojson {file : Caminho do arquivo GeoJSON}';
    protected $description = 'Importa dados de municípios do GeoJSON para o banco de dados';

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!File::exists($file)) {
            $this->error("Arquivo não encontrado: {$file}");
            return 1;
        }

        $this->info("Lendo arquivo GeoJSON...");
        $geojson = json_decode(File::get($file), true);

        if (!isset($geojson['features'])) {
            $this->error("Arquivo GeoJSON inválido: não contém features");
            return 1;
        }

        $total = count($geojson['features']);
        $this->info("Total de municípios encontrados: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();
        try {
            foreach ($geojson['features'] as $feature) {
                $properties = $feature['properties'];
                $geometry = $feature['geometry'];

                // Simplificar geometria
                $geomSimplificado = $this->simplificarGeometria($geometry);
                
                // Calcular centróide
                $centroide = $this->calcularCentroide($geometry);

                DB::table('municipios_simplificado')->insert([
                    'codigo_ibge' => $properties['CD_MUN'],
                    'nome' => $properties['NM_MUN'],
                    'uf' => $properties['SIGLA_UF'],
                    'geom' => json_encode($geometry),
                    'geom_simplificado' => json_encode($geomSimplificado),
                    'centroide' => json_encode($centroide)
                ]);

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);
            $this->info("Importação concluída com sucesso!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erro durante a importação: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function simplificarGeometria($geometry)
    {
        // Implementação simples de simplificação
        // Mantém apenas um ponto a cada 5 pontos
        if ($geometry['type'] === 'MultiPolygon') {
            $simplificado = [
                'type' => 'MultiPolygon',
                'coordinates' => []
            ];

            foreach ($geometry['coordinates'] as $polygon) {
                $simplificadoPolygon = [];
                foreach ($polygon as $ring) {
                    $simplificadoRing = [];
                    for ($i = 0; $i < count($ring); $i += 5) {
                        $simplificadoRing[] = $ring[$i];
                    }
                    // Garantir que o primeiro e último ponto sejam iguais (polígono fechado)
                    if (end($simplificadoRing) !== $simplificadoRing[0]) {
                        $simplificadoRing[] = $simplificadoRing[0];
                    }
                    $simplificadoPolygon[] = $simplificadoRing;
                }
                $simplificado['coordinates'][] = $simplificadoPolygon;
            }

            return $simplificado;
        }

        return $geometry;
    }

    private function calcularCentroide($geometry)
    {
        if ($geometry['type'] === 'MultiPolygon') {
            $somaX = 0;
            $somaY = 0;
            $totalPontos = 0;

            foreach ($geometry['coordinates'] as $polygon) {
                foreach ($polygon as $ring) {
                    foreach ($ring as $point) {
                        $somaX += $point[0];
                        $somaY += $point[1];
                        $totalPontos++;
                    }
                }
            }

            if ($totalPontos > 0) {
                return [
                    'type' => 'Point',
                    'coordinates' => [
                        $somaX / $totalPontos,
                        $somaY / $totalPontos
                    ]
                ];
            }
        }

        return [
            'type' => 'Point',
            'coordinates' => [0, 0]
        ];
    }
} 