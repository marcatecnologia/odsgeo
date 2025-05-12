<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WmsCamada extends Model
{
    use HasFactory;

    protected $table = 'wms_camadas';

    protected $fillable = [
        'uf',
        'tipo',
        'tema',
        'url',
        'ativo',
        'descricao',
        'data_sync'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_sync' => 'datetime'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getTiposDisponiveis(): array
    {
        return [
            'sigef_particular' => 'SIGEF Particular',
            'sigef_publico' => 'SIGEF Público',
            'imoveis_privado' => 'Imóveis Privados',
            'imoveis_publico' => 'Imóveis Públicos',
            'parcelageo' => 'Parcelas Georreferenciadas',
            'assentamentos' => 'Assentamentos',
            'reconhecimento' => 'Reconhecimento',
            'quilombolas' => 'Quilombolas'
        ];
    }

    public function getDescricaoFormatadaAttribute(): string
    {
        return $this->descricao ?? "Camada {$this->tipo} - {$this->uf}";
    }
} 