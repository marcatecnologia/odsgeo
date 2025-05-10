<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Services\DiretorioService;
use App\Traits\HasFiles;

class Servico extends Model
{
    use HasFactory, SoftDeletes, HasFiles;

    protected $fillable = [
        'projeto_id',
        'nome',
        'tipo',
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

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($servico) {
            if (!$servico->projeto_id) {
                throw new \Exception('É necessário selecionar um projeto para criar o serviço.');
            }

            $projeto = Projeto::find($servico->projeto_id);
            if (!$projeto) {
                throw new \Exception('Projeto não encontrado.');
            }

            $diretorioService = app(DiretorioService::class);
            $servico->diretorio = $diretorioService->criarDiretorioServico(
                $projeto->diretorio,
                $servico->nome
            );
            $diretorioService->criarEstruturaDiretoriosServico($servico->diretorio);
        });

        static::deleting(function ($servico) {
            if ($servico->diretorio) {
                $diretorioService = app(DiretorioService::class);
                $diretorioService->removerDiretorio($servico->diretorio);
            }
        });
    }
}
