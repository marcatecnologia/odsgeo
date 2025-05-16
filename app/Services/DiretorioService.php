<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DiretorioService
{
    protected $estruturaBase = [
        'planilhas' => [],
        'memoriais' => [],
        'shapes' => [],
        'coordenadas' => [],
        'mapas' => [],
        'relatorios' => [],
        'documentos' => []
    ];

    public function criarEstruturaDiretoriosServico($diretorioBase)
    {
        foreach ($this->estruturaBase as $categoria => $subcategorias) {
            $caminhoCategoria = $diretorioBase . '/' . $categoria;
            Storage::makeDirectory($caminhoCategoria);

            if (!empty($subcategorias)) {
                foreach ($subcategorias as $subcategoria) {
                    Storage::makeDirectory($caminhoCategoria . '/' . $subcategoria);
                }
            }
        }
    }

    public function criarDiretorioServico($diretorioProjeto, $nomeServico)
    {
        $slug = Str::slug($nomeServico);
        $diretorio = $diretorioProjeto . '/' . $slug;
        
        if (!Storage::exists($diretorio)) {
            Storage::makeDirectory($diretorio);
        }
        
        return $diretorio;
    }

    public function removerDiretorio($diretorio)
    {
        if (Storage::exists($diretorio)) {
            Storage::deleteDirectory($diretorio);
        }
    }

    public function listarArquivosPorCategoria($diretorioServico, $categoria)
    {
        $caminho = $diretorioServico . '/' . $categoria;
        
        if (!Storage::exists($caminho)) {
            return [];
        }

        return Storage::files($caminho);
    }

    public function moverArquivo($arquivo, $categoriaDestino, $diretorioServico)
    {
        $nomeArquivo = basename($arquivo);
        $destino = $diretorioServico . '/' . $categoriaDestino . '/' . $nomeArquivo;
        
        Storage::move($arquivo, $destino);
        
        return $destino;
    }
} 