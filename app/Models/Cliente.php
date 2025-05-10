<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Services\DiretorioService;
use App\Traits\HasFiles;

class Cliente extends Model
{
    use HasFactory, SoftDeletes, HasFiles;

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'cpf_cnpj',
        'tipo_pessoa',
        'observacoes',
        'diretorio',
    ];

    public function projetos()
    {
        return $this->hasMany(Projeto::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cliente) {
            $diretorioService = app(DiretorioService::class);
            $cliente->diretorio = $diretorioService->criarDiretorioCliente($cliente->nome);
            $diretorioService->criarEstruturaDiretorios($cliente->diretorio);
        });

        static::deleting(function ($cliente) {
            $diretorioService = app(DiretorioService::class);
            $diretorioService->removerDiretorio($cliente->diretorio);
        });
    }
}
