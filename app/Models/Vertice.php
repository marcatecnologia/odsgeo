<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vertice extends Model
{
    use HasFactory;

    protected $fillable = [
        'planilha_ods_id',
        'nome_ponto',
        'coordenada_x',
        'coordenada_y',
        'altitude',
        'tipo_marco',
        'codigo_sirgas',
        'ordem',
    ];

    protected $casts = [
        'coordenada_x' => 'decimal:6',
        'coordenada_y' => 'decimal:6',
        'altitude' => 'decimal:2',
    ];

    public function planilhaOds()
    {
        return $this->belongsTo(PlanilhaOds::class, 'planilha_ods_id');
    }

    public function segmentosIniciais()
    {
        return $this->hasMany(Segmento::class, 'vertice_inicial_id');
    }

    public function segmentosFinais()
    {
        return $this->hasMany(Segmento::class, 'vertice_final_id');
    }
} 