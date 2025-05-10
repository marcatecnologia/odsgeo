<?php

namespace App\Traits;

use App\Services\FileService;
use Illuminate\Http\UploadedFile;

trait HasFiles
{
    public function uploadArquivo(UploadedFile $arquivo, string $subdiretorio = 'arquivos'): string
    {
        $fileService = app(FileService::class);
        return $fileService->uploadArquivo($arquivo, $this->diretorio, $subdiretorio);
    }

    public function downloadArquivo(string $caminho): ?string
    {
        $fileService = app(FileService::class);
        return $fileService->downloadArquivo($caminho);
    }

    public function removerArquivo(string $caminho): bool
    {
        $fileService = app(FileService::class);
        return $fileService->removerArquivo($caminho);
    }

    public function listarArquivos(string $subdiretorio = 'arquivos'): array
    {
        $fileService = app(FileService::class);
        return $fileService->listarArquivos($this->diretorio, $subdiretorio);
    }

    public function obterUrlArquivo(string $caminho): ?string
    {
        $fileService = app(FileService::class);
        return $fileService->obterUrlArquivo($caminho);
    }
} 