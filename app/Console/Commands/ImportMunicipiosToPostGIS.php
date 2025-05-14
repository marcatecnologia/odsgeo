<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportMunicipiosToPostGIS extends Command
{
    protected $signature = 'municipios:import-postgis {file : Caminho do arquivo GeoJSON}';
    protected $description = 'Importa dados de municípios do GeoJSON para o PostGIS';

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

                // Converter geometria para WKT
                $geomWKT = $this->geometryToWKT($geometry);
                $geomSimplificadoWKT = $this->simplificarGeometria($geomWKT);

                // Calcular centróide
                $centroide = $this->calcularCentroide($geomWKT);

                DB::table('municipios_simplificado')->insert([
                    'codigo_ibge' => $properties['CD_MUN'],
                    'nome' => $properties['NM_MUN'],
                    'uf' => $properties['SIGLA_UF'],
                    'centroide' => json_encode($centroide)
                ]);

                // Atualizar geometrias usando PostGIS
                DB::statement("UPDATE municipios_simplificado SET 
                    geom = ST_Multi(ST_GeomFromText(?, 4326)),
                    geom_simplificado = ST_Multi(ST_SimplifyPreserveTopology(ST_GeomFromText(?, 4326), 0.0001))
                    WHERE codigo_ibge = ?", 
                    [$geomWKT, $geomSimplificadoWKT, $properties['CD_MUN']]
                );

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->info("\nImportação concluída com sucesso!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\nErro durante a importação: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function geometryToWKT($geometry)
    {
        if ($geometry['type'] === 'Polygon') {
            $coordinates = $geometry['coordinates'];
            $wkt = 'POLYGON((';
            foreach ($coordinates[0] as $coord) {
                $wkt .= $coord[0] . ' ' . $coord[1] . ',';
            }
            $wkt = rtrim($wkt, ',') . '))';
            return $wkt;
        }
        return null;
    }

    protected function simplificarGeometria($wkt)
    {
        return DB::select("SELECT ST_AsText(ST_SimplifyPreserveTopology(ST_GeomFromText(?, 4326), 0.0001)) as simplified", [$wkt])[0]->simplified;
    }

    protected function calcularCentroide($wkt)
    {
        $result = DB::select("SELECT ST_AsGeoJSON(ST_Centroid(ST_GeomFromText(?, 4326))) as centroid", [$wkt])[0]->centroid;
        $centroid = json_decode($result, true);
        return [
            'lat' => $centroid['coordinates'][1],
            'lng' => $centroid['coordinates'][0]
        ];
    }
} 