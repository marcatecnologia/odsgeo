<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipioSimplificado extends Model
{
    protected $table = 'municipios_simplificado';
    
    protected $fillable = [
        'codigo_ibge',
        'nome',
        'uf',
        'geometry'
    ];

    protected $casts = [
        'geometry' => 'array'
    ];

    public function getCentroideAttribute()
    {
        if (!$this->geometry) {
            return null;
        }

        // Calcula o centróide usando PostGIS
        $centroide = \DB::select("
            SELECT ST_AsGeoJSON(ST_Centroid(geometry)) as centroide 
            FROM municipios_simplificado 
            WHERE id = ?
        ", [$this->id])[0]->centroide;

        return json_decode($centroide, true);
    }
} 