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
        $key = 'clientes_list_' . md5($search);
        return Cache::tags(['clientes'])->remember($key, $this->ttl, function () use ($search) {
            return Cliente::query()
                ->when($search, function ($query) use ($search) {
                    $query->where('nome', 'like', "%{$search}%");
                })
                ->withCount('projetos')
                ->orderBy('nome')
                ->get();
        });
    }

    public function getProjetosList($clienteId, $search = '')
    {
        $key = "projetos_list_{$clienteId}_" . md5($search);
        return Cache::tags(['projetos'])->remember($key, $this->ttl, function () use ($clienteId, $search) {
            return Projeto::query()
                ->where('cliente_id', $clienteId)
                ->when($search, function ($query) use ($search) {
                    $query->where('nome', 'like', "%{$search}%");
                })
                ->withCount('servicos')
                ->orderBy('nome')
                ->get();
        });
    }

    public function getServicosList($projetoId, $search = '')
    {
        $key = "servicos_list_{$projetoId}_" . md5($search);
        return Cache::tags(['servicos'])->remember($key, $this->ttl, function () use ($projetoId, $search) {
            return Servico::query()
                ->where('projeto_id', $projetoId)
                ->when($search, function ($query) use ($search) {
                    $query->where('nome', 'like', "%{$search}%");
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