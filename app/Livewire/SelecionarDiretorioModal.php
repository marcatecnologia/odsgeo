<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\Servico;
use App\Services\CacheService;
use App\Events\ServicoAtualizado;
use App\Jobs\LimparCacheDiretorio;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class SelecionarDiretorioModal extends Component
{
    public Collection $clientes;
    public Collection $projetos;
    public Collection $servicos;

    public $cliente_id = null;
    public $projeto_id = null;
    public $servico_id = null;
    
    public $searchCliente = '';
    public $searchProjeto = '';
    public $searchServico = '';

    protected $queryString = [
        'cliente_id' => ['except' => ''],
        'projeto_id' => ['except' => ''],
        'servico_id' => ['except' => '']
    ];

    protected $cacheService;

    public function boot(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function mount()
    {
        // Inicializa as coleções vazias
        $this->clientes = collect();
        $this->projetos = collect();
        $this->servicos = collect();
        
        $this->loadClientes();
        
        $currentServiceId = session('current_service_id');
        if ($currentServiceId) {
            $servico = $this->cacheService->getServicoCompleto($currentServiceId);
            
            if ($servico) {
                $this->servico_id = $servico->id;
                $this->projeto_id = $servico->projeto_id;
                $this->cliente_id = $servico->projeto->cliente_id;
                
                $this->loadProjetos();
                $this->loadServicos();
            }
        }
    }

    protected function loadClientes()
    {
        $this->clientes = $this->cacheService->getClientesList($this->searchCliente) ?? collect();
    }

    protected function loadProjetos()
    {
        if ($this->cliente_id) {
            $this->projetos = $this->cacheService->getProjetosList($this->cliente_id, $this->searchProjeto) ?? collect();
        } else {
            $this->projetos = collect();
        }
    }

    protected function loadServicos()
    {
        if ($this->projeto_id) {
            $this->servicos = $this->cacheService->getServicosList($this->projeto_id, $this->searchServico) ?? collect();
        } else {
            $this->servicos = collect();
        }
    }

    public function updatedSearchCliente()
    {
        $this->loadClientes();
    }

    public function updatedSearchProjeto()
    {
        if ($this->cliente_id) {
            $this->loadProjetos();
        }
    }

    public function updatedSearchServico()
    {
        if ($this->projeto_id) {
            $this->loadServicos();
        }
    }

    public function updatedClienteId($value)
    {
        $this->projeto_id = null;
        $this->servico_id = null;
        $this->servicos = collect();
        $this->searchProjeto = '';
        $this->searchServico = '';
        
        if ($value) {
            $this->loadProjetos();
        } else {
            $this->projetos = collect();
        }
    }

    public function updatedProjetoId($value)
    {
        $this->servico_id = null;
        $this->searchServico = '';
        
        if ($value) {
            $this->loadServicos();
        } else {
            $this->servicos = collect();
        }
    }

    public function confirmarSelecao()
    {
        if (!$this->servico_id) {
            Notification::make()
                ->title('Erro')
                ->body('Por favor, selecione um serviço.')
                ->danger()
                ->send();
            return;
        }

        $servico = $this->cacheService->getServicoCompleto($this->servico_id);

        if (
            !$servico ||
            (int)$servico->projeto_id !== (int)$this->projeto_id ||
            (int)$servico->projeto->cliente_id !== (int)$this->cliente_id
        ) {
            Notification::make()
                ->title('Erro')
                ->body('Serviço inválido para o projeto/cliente selecionado.')
                ->danger()
                ->send();
            return;
        }

        session()->put('current_service_id', $this->servico_id);
        
        // Dispara eventos e jobs
        event(new ServicoAtualizado($servico));
        LimparCacheDiretorio::dispatch('servico', $this->servico_id);

        Notification::make()
            ->title('Sucesso')
            ->body('Diretório atualizado com sucesso!')
            ->success()
            ->send();

        $this->dispatch('close-modal');
        $this->dispatch('diretorio-atualizado');
    }

    public function atualizarClientes()
    {
        LimparCacheDiretorio::dispatch('cliente', $this->cliente_id);
        $this->loadClientes();
    }

    public function atualizarProjetos()
    {
        if ($this->cliente_id) {
            LimparCacheDiretorio::dispatch('projeto', $this->projeto_id);
            $this->loadProjetos();
        }
    }

    public function atualizarServicos()
    {
        if ($this->projeto_id) {
            LimparCacheDiretorio::dispatch('servico', $this->servico_id);
            $this->loadServicos();
        }
    }

    protected $listeners = ['atualizarTodos' => 'atualizarTudo'];

    public function atualizarTudo()
    {
        LimparCacheDiretorio::dispatch('todos');
        $this->atualizarClientes();
        $this->atualizarProjetos();
        $this->atualizarServicos();
    }

    public function render()
    {
        return view('livewire.selecionar-diretorio-modal');
    }
} 