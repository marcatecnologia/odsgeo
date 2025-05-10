<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Services\DiretorioService;
use App\Traits\HasFiles;

class Projeto extends Model
{
    use HasFactory, SoftDeletes, HasFiles;

    protected $fillable = [
        'cliente_id',
        'nome',
        'descricao',
        'status',
        'data_inicio',
        'data_fim',
        'diretorio',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servicos()
    {
        return $this->hasMany(Servico::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($projeto) {
            $diretorioService = app(DiretorioService::class);
            $projeto->diretorio = $diretorioService->criarDiretorioProjeto(
                $projeto->cliente->diretorio,
                $projeto->nome
            );
            $diretorioService->criarEstruturaDiretoriosProjeto($projeto->diretorio);
        });

        static::deleting(function ($projeto) {
            $diretorioService = app(DiretorioService::class);
            $diretorioService->removerDiretorio($projeto->diretorio);
        });
    }
}
