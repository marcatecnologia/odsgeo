<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMunicipiosSimplificadoTable extends Migration
{
    public function up()
    {
        Schema::create('municipios_simplificado', function (Blueprint $table) {
            $table->string('codigo_ibge', 7)->primary();
            $table->string('nome', 100);
            $table->string('uf', 2);
            $table->json('centroide');
            
            // Índices
            $table->index('uf');
            $table->index('nome');
        });

        // Adicionar colunas geométricas usando PostGIS
        DB::statement('ALTER TABLE municipios_simplificado ADD COLUMN geom geometry(MultiPolygon, 4326)');
        DB::statement('ALTER TABLE municipios_simplificado ADD COLUMN geom_simplificado geometry(MultiPolygon, 4326)');
        
        // Criar índices espaciais
        DB::statement('CREATE INDEX municipios_geom_idx ON municipios_simplificado USING GIST (geom)');
        DB::statement('CREATE INDEX municipios_geom_simplificado_idx ON municipios_simplificado USING GIST (geom_simplificado)');
        
        // Criar função para simplificar geometria
        DB::statement('
            CREATE OR REPLACE FUNCTION simplificar_geometria_municipio()
            RETURNS trigger AS $$
            BEGIN
                -- Simplificar geometria com tolerância de 0.0001 (aproximadamente 11 metros)
                NEW.geom_simplificado := ST_SimplifyPreserveTopology(NEW.geom, 0.0001);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Criar trigger para simplificar geometria automaticamente
        DB::statement('
            CREATE TRIGGER simplificar_geometria_municipio_trigger
            BEFORE INSERT OR UPDATE OF geom ON municipios_simplificado
            FOR EACH ROW
            EXECUTE FUNCTION simplificar_geometria_municipio();
        ');
    }

    public function down()
    {
        Schema::dropIfExists('municipios_simplificado');
    }
} 