<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Servico;
use App\Models\Projeto;
use App\Models\Cliente;

class CacheService
{
    protected $tags = ['servicos', 'projetos', 'clientes'];
    protected $ttl = 3600; // 1 hora

    public function getServicoCompleto($id)
    {
        return Cache::tags($this->tags)
            ->remember('servico_completo_' . $id, $this->ttl, function() use ($id) {
                return Servico::with([
                    'projeto.cliente',
                    'projeto.servicos' => function($query) {
                        $query->orderBy('nome');
                    }
                ])->find($id);
            });
    }

    public function getClientesList($search = '')
    {
        $search = trim($search);

        if ($search) {
            // Busca por nome, email ou telefone, sem cache
            return Cliente::query()
                ->where(function ($query) use ($search) {
                    $query->where('nome', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('telefone', 'like', "%{$search}%");
                })
                ->withCount('projetos')
                ->orderBy('nome')
                ->get();
        }
        // Cacheia apenas a lista completa
        $key = 'clientes_list_all';
        return Cache::tags(['clientes'])->remember($key, $this->ttl, function () {
            return Cliente::withCount('projetos')->orderBy('nome')->get();
        });
    }

    public function getProjetosList($clienteId, $search = '')
    {
        $search = trim($search);
        $key = "projetos_list_{$clienteId}_" . md5($search);
        return Cache::tags(['projetos'])->remember($key, $this->ttl, function () use ($clienteId, $search) {
            return Projeto::query()
                ->where('cliente_id', $clienteId)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nome', 'like', "%{$search}%")
                          ->orWhere('descricao', 'like', "%{$search}%");
                    });
                })
                ->withCount('servicos')
                ->orderBy('nome')
                ->get();
        });
    }

    public function getServicosList($projetoId, $search = '')
    {
        $search = trim($search);
        $key = "servicos_list_{$projetoId}_" . md5($search);
        return Cache::tags(['servicos'])->remember($key, $this->ttl, function () use ($projetoId, $search) {
            return Servico::query()
                ->where('projeto_id', $projetoId)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nome', 'like', "%{$search}%")
                          ->orWhere('descricao', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nome')
                ->get();
        });
    }

    public function flushCache()
    {
        Cache::tags($this->tags)->flush();
    }

    public function forgetServico($id)
    {
        Cache::tags($this->tags)->forget('servico_completo_' . $id);
        Cache::tags($this->tags)->forget('servicos_projeto_' . $id);
    }

    public function forgetCliente($id)
    {
        Cache::tags(['clientes'])->flush();
    }

    public function forgetProjeto($id)
    {
        Cache::tags(['projetos'])->flush();
    }
} 