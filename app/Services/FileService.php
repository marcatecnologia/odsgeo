<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    protected $diretorioService;

    public function __construct(DiretorioService $diretorioService)
    {
        $this->diretorioService = $diretorioService;
    }

    public function uploadArquivo(UploadedFile $arquivo, string $diretorio, string $subdiretorio = 'arquivos'): string
    {
        $nomeArquivo = Str::slug(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . uniqid() . '.' . $arquivo->getClientOriginalExtension();
        $caminhoCompleto = $diretorio . '/' . $subdiretorio . '/' . $nomeArquivo;
        
        Storage::disk('clientes')->putFileAs(
            $diretorio . '/' . $subdiretorio,
            $arquivo,
            $nomeArquivo
        );

        return $caminhoCompleto;
    }

    public function downloadArquivo(string $caminho): ?string
    {
        if (Storage::disk('clientes')->exists($caminho)) {
            return Storage::disk('clientes')->path($caminho);
        }

        return null;
    }

    public function removerArquivo(string $caminho): bool
    {
        if (Storage::disk('clientes')->exists($caminho)) {
            return Storage::disk('clientes')->delete($caminho);
        }

        return false;
    }

    public function listarArquivos(string $diretorio, string $subdiretorio = 'arquivos'): array
    {
        $caminhoCompleto = $diretorio . '/' . $subdiretorio;
        
        if (!Storage::disk('clientes')->exists($caminhoCompleto)) {
            return [];
        }

        return Storage::disk('clientes')->files($caminhoCompleto);
    }

    public function obterUrlArquivo(string $caminho): ?string
    {
        if (Storage::disk('clientes')->exists($caminho)) {
            return Storage::disk('clientes')->url($caminho);
        }

        return null;
    }
} 