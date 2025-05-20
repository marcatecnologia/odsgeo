<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeoServerService;

class DiagnosticoGeoServerCommand extends Command
{
    protected $signature = 'diagnostico:geoserver-estado {uf}';
    protected $description = 'Diagnostica a busca de geometria de um estado no GeoServer e exibe a resposta completa';

    public function handle()
    {
        $uf = $this->argument('uf');
        $this->info("Buscando geometria do estado: $uf");

        $service = app(GeoServerService::class);

        try {
            $geometry = $service->getEstadoGeometry($uf);

            $this->info('Retorno do método getEstadoGeometry:');
            dump($geometry);

            if (is_array($geometry) && isset($geometry[0]['type']) && $geometry[0]['type'] === 'FeatureCollection') {
                $this->warn('ATENÇÃO: O retorno é um array de FeatureCollection!');
            } elseif (isset($geometry['type']) && $geometry['type'] === 'FeatureCollection') {
                $this->info('Retorno correto: objeto FeatureCollection.');
            } else {
                $this->error('Retorno inesperado!');
            }

            if (isset($geometry['features'])) {
                $this->info('Quantidade de features retornadas: ' . count($geometry['features']));
            }

        } catch (\Exception $e) {
            $this->error('Erro ao buscar geometria: ' . $e->getMessage());
        }
    }
} 