<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'municipios';
    
    protected $fillable = [
        'cd_mun',
        'nm_mun',
        'cd_rgi',
        'nm_rgi',
        'cd_rgint',
        'nm_rgint',
        'cd_uf',
        'nm_uf',
        'sigla_uf',
        'cd_regia',
        'nm_regia',
        'sigla_rg',
        'cd_concu',
        'nm_concu',
        'area_km2',
        'geometry'
    ];

    protected $casts = [
        'geometry' => 'array',
        'area_km2' => 'float'
    ];
} 