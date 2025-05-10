<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DiretorioService
{
    protected $basePath = 'clientes';

    public function criarDiretorioCliente(string $nome): string
    {
        $diretorio = $this->basePath . '/' . Str::slug($nome) . '-' . uniqid();
        Storage::makeDirectory($diretorio);
        return $diretorio;
    }

    public function criarDiretorioProjeto(string $clienteDiretorio, string $nome): string
    {
        $diretorio = $clienteDiretorio . '/projetos/' . Str::slug($nome) . '-' . uniqid();
        Storage::makeDirectory($diretorio);
        return $diretorio;
    }

    public function criarDiretorioServico(string $projetoDiretorio, string $nome): string
    {
        $diretorio = $projetoDiretorio . '/servicos/' . Str::slug($nome) . '-' . uniqid();
        Storage::makeDirectory($diretorio);
        return $diretorio;
    }

    public function criarEstruturaDiretorios(string $clienteDiretorio): void
    {
        // Criar subdiretórios padrão para o cliente
        Storage::makeDirectory($clienteDiretorio . '/projetos');
        Storage::makeDirectory($clienteDiretorio . '/documentos');
        Storage::makeDirectory($clienteDiretorio . '/arquivos');
    }

    public function criarEstruturaDiretoriosProjeto(string $projetoDiretorio): void
    {
        // Criar subdiretórios padrão para o projeto
        Storage::makeDirectory($projetoDiretorio . '/servicos');
        Storage::makeDirectory($projetoDiretorio . '/documentos');
        Storage::makeDirectory($projetoDiretorio . '/arquivos');
    }

    public function criarEstruturaDiretoriosServico(string $servicoDiretorio): void
    {
        // Criar subdiretórios padrão para o serviço
        Storage::makeDirectory($servicoDiretorio . '/documentos');
        Storage::makeDirectory($servicoDiretorio . '/arquivos');
        Storage::makeDirectory($servicoDiretorio . '/ods');
    }

    public function removerDiretorio(string $diretorio): bool
    {
        return Storage::deleteDirectory($diretorio);
    }
} 