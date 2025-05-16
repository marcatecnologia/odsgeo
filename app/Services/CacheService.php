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
        $key = $search ? 'clientes_search_' . $search : 'clientes_list';
        
        return Cache::tags($this->tags)
            ->remember($key, $this->ttl, function() use ($search) {
                $query = Cliente::query()->orderBy('nome');
                
                if ($search) {
                    $query->where('nome', 'like', '%' . $search . '%');
                }
                
                return $query->get();
            });
    }

    public function getProjetosList($clienteId, $search = '')
    {
        $key = 'projetos_cliente_' . $clienteId . ($search ? '_search_' . $search : '');
        
        return Cache::tags($this->tags)
            ->remember($key, $this->ttl, function() use ($clienteId, $search) {
                $query = Projeto::where('cliente_id', $clienteId)
                    ->orderBy('nome');
                
                if ($search) {
                    $query->where('nome', 'like', '%' . $search . '%');
                }
                
                return $query->get();
            });
    }

    public function getServicosList($projetoId, $search = '')
    {
        $key = 'servicos_projeto_' . $projetoId . ($search ? '_search_' . $search : '');
        
        return Cache::tags($this->tags)
            ->remember($key, $this->ttl, function() use ($projetoId, $search) {
                $query = Servico::where('projeto_id', $projetoId)
                    ->orderBy('nome');
                
                if ($search) {
                    $query->where('nome', 'like', '%' . $search . '%');
                }
                
                return $query->get();
            });
    }

    public function flushCache()
    {
        Cache::tags($this->tags)->flush();
    }

    public function forgetServico($id)
    {
        Cache::tags($this->tags)->forget('servico_completo_' . $id);
    }

    public function forgetCliente($id)
    {
        Cache::tags($this->tags)->forget('clientes_list');
        Cache::tags($this->tags)->forget('clientes_search_*');
    }

    public function forgetProjeto($id)
    {
        Cache::tags($this->tags)->forget('projetos_cliente_' . $id);
        Cache::tags($this->tags)->forget('projetos_search_*');
    }
} 