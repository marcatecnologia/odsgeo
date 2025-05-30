<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\Servico;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DiretorioService
{
    protected $basePath = 'clientes';

    public function criarDiretorioCliente($nome)
    {
        $diretorio = $this->basePath . '/' . Str::slug($nome);
        Storage::makeDirectory($diretorio);
        return $diretorio;
    }

    public function criarDiretorioProjeto($diretorioCliente, $nome)
    {
        $diretorio = $diretorioCliente . '/projetos/' . Str::slug($nome);
        Storage::makeDirectory($diretorio);
        return $diretorio;
    }

    public function criarDiretorioServico($diretorioProjeto, $nome)
    {
        $diretorio = $diretorioProjeto . '/servicos/' . Str::slug($nome);
        Storage::makeDirectory($diretorio);
        $this->criarEstruturaDiretoriosServico($diretorio);
        return $diretorio;
    }

    public function criarEstruturaDiretoriosServico($diretorio)
    {
        $estrutura = [
            'planilhas' => [
                'cadernetas',
                'parcelas',
                'coordenadas',
                'outros'
            ],
            'memoriais' => [
                'descritivos',
                'fotograficos',
                'outros'
            ],
            'shapes' => [
                'parcelas',
                'limites',
                'outros'
            ],
            'coordenadas' => [
                'brutos',
                'processados',
                'outros'
            ],
            'mapas' => [
                'parcelas',
                'limites',
                'outros'
            ],
            'relatorios' => [
                'tecnicos',
                'administrativos',
                'outros'
            ],
            'documentos' => [
                'contratos',
                'certidoes',
                'outros'
            ]
        ];

        foreach ($estrutura as $categoria => $subcategorias) {
            $caminhoCategoria = $diretorio . '/' . $categoria;
            Storage::makeDirectory($caminhoCategoria);

            foreach ($subcategorias as $subcategoria) {
                Storage::makeDirectory($caminhoCategoria . '/' . $subcategoria);
            }
        }
    }

    public function removerDiretorio($diretorio)
    {
        if (Storage::exists($diretorio)) {
            Storage::deleteDirectory($diretorio);
        }
    }

    public function getDiretorioCliente($clienteId)
    {
        $cliente = Cliente::find($clienteId);
        return $this->basePath . '/' . Str::slug($cliente->nome);
    }

    public function getDiretorioProjeto($projetoId)
    {
        $projeto = Projeto::with('cliente')->find($projetoId);
        return $this->getDiretorioCliente($projeto->cliente_id) . '/projetos/' . Str::slug($projeto->nome);
    }

    public function getDiretorioServico($servicoId)
    {
        $servico = Servico::with('projeto.cliente')->find($servicoId);
        return $this->getDiretorioProjeto($servico->projeto_id) . '/servicos/' . Str::slug($servico->nome);
    }

    public function listarArquivosPorCategoria($diretorioServico, $categoria, $subcategoria = null)
    {
        $caminho = $diretorioServico . '/' . $categoria;
        if ($subcategoria) {
            $caminho .= '/' . $subcategoria;
        }
        
        if (!Storage::exists($caminho)) {
            return [];
        }

        return Storage::files($caminho);
    }

    public function moverArquivo($arquivo, $categoria, $subcategoria, $diretorioServico)
    {
        $nomeArquivo = basename($arquivo);
        $destino = $diretorioServico . '/' . $categoria . '/' . $subcategoria . '/' . $nomeArquivo;
        
        Storage::move($arquivo, $destino);
        
        return $destino;
    }

    public function getEstruturaCategorias()
    {
        return [
            'planilhas' => [
                'cadernetas',
                'parcelas',
                'coordenadas',
                'outros'
            ],
            'memoriais' => [
                'descritivos',
                'fotograficos',
                'outros'
            ],
            'shapes' => [
                'parcelas',
                'limites',
                'outros'
            ],
            'coordenadas' => [
                'brutos',
                'processados',
                'outros'
            ],
            'mapas' => [
                'parcelas',
                'limites',
                'outros'
            ],
            'relatorios' => [
                'tecnicos',
                'administrativos',
                'outros'
            ],
            'documentos' => [
                'contratos',
                'certidoes',
                'outros'
            ]
        ];
    }
} 