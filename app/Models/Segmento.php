<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Segmento extends Model
{
    use HasFactory;

    protected $fillable = [
        'planilha_ods_id',
        'vertice_inicial_id',
        'vertice_final_id',
        'azimute',
        'distancia',
        'confrontante',
        'tipo_limite',
        'ordem',
    ];

    protected $casts = [
        'azimute' => 'decimal:6',
        'distancia' => 'decimal:2',
    ];

    public function planilhaOds()
    {
        return $this->belongsTo(PlanilhaOds::class, 'planilha_ods_id');
    }

    public function verticeInicial()
    {
        return $this->belongsTo(Vertice::class, 'vertice_inicial_id');
    }

    public function verticeFinal()
    {
        return $this->belongsTo(Vertice::class, 'vertice_final_id');
    }
} 