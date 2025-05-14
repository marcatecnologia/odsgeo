<?php

namespace App\Services;

use App\Models\Municipio;
use Illuminate\Support\Facades\Log;

class GeoJsonImportService
{
    public function importFromGeoJson(array $geoJson)
    {
        try {
            if (!isset($geoJson['features']) || !is_array($geoJson['features'])) {
                throw new \Exception('GeoJSON inválido: features não encontrado ou não é um array');
            }

            foreach ($geoJson['features'] as $feature) {
                if (!isset($feature['properties']) || !isset($feature['geometry'])) {
                    continue;
                }

                $properties = $feature['properties'];
                $geometry = $feature['geometry'];

                Municipio::updateOrCreate(
                    ['cd_mun' => $properties['CD_MUN']],
                    [
                        'nm_mun' => $properties['NM_MUN'],
                        'cd_rgi' => $properties['CD_RGI'],
                        'nm_rgi' => $properties['NM_RGI'],
                        'cd_rgint' => $properties['CD_RGINT'],
                        'nm_rgint' => $properties['NM_RGINT'],
                        'cd_uf' => $properties['CD_UF'],
                        'nm_uf' => $properties['NM_UF'],
                        'sigla_uf' => $properties['SIGLA_UF'],
                        'cd_regia' => $properties['CD_REGIA'],
                        'nm_regia' => $properties['NM_REGIA'],
                        'sigla_rg' => $properties['SIGLA_RG'],
                        'cd_concu' => $properties['CD_CONCU'],
                        'nm_concu' => $properties['NM_CONCU'],
                        'area_km2' => $properties['AREA_KM2'],
                        'geometry' => $geometry
                    ]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao importar GeoJSON: ' . $e->getMessage());
            throw $e;
        }
    }
} 