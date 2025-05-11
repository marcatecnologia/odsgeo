<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\Servico;
use Filament\Notifications\Notification;

class SelecionarDiretorioModal extends Component
{
    public $clientes = [];
    public $projetos = [];
    public $servicos = [];

    public $cliente_id = null;
    public $projeto_id = null;
    public $servico_id = null;

    public function mount()
    {
        $this->clientes = Cliente::orderBy('nome')->get();
        
        // Recupera o serviço atual da sessão
        $currentServiceId = session('current_service_id');
        if ($currentServiceId) {
            $servico = Servico::find($currentServiceId);
            if ($servico) {
                $this->servico_id = $servico->id;
                $this->projeto_id = $servico->projeto_id;
                $this->cliente_id = $servico->projeto->cliente_id;
                
                // Carrega os projetos e serviços relacionados
                $this->projetos = Projeto::where('cliente_id', $this->cliente_id)->orderBy('nome')->get();
                $this->servicos = Servico::where('projeto_id', $this->projeto_id)->orderBy('nome')->get();
            }
        }
    }

    public function updatedClienteId($value)
    {
        $this->projetos = Projeto::where('cliente_id', $value)->orderBy('nome')->get();
        $this->projeto_id = null;
        $this->servicos = [];
        $this->servico_id = null;
    }

    public function updatedProjetoId($value)
    {
        $this->servicos = Servico::where('projeto_id', $value)->orderBy('nome')->get();
        $this->servico_id = null;
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

        // Valida se o serviço pertence ao projeto e cliente selecionados
        $servico = Servico::where('id', $this->servico_id)
            ->whereHas('projeto', function ($query) {
                $query->where('id', $this->projeto_id)
                    ->where('cliente_id', $this->cliente_id);
            })
            ->first();

        if (!$servico) {
            Notification::make()
                ->title('Erro')
                ->body('Serviço inválido para o projeto/cliente selecionado.')
                ->danger()
                ->send();
            return;
        }

        // Salva o ID do serviço na sessão
        session()->put('current_service_id', $this->servico_id);

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
        $this->clientes = \App\Models\Cliente::orderBy('nome')->get();
    }

    public function atualizarProjetos()
    {
        if ($this->cliente_id) {
            $this->projetos = \App\Models\Projeto::where('cliente_id', $this->cliente_id)->orderBy('nome')->get();
        }
    }

    public function atualizarServicos()
    {
        if ($this->projeto_id) {
            $this->servicos = \App\Models\Servico::where('projeto_id', $this->projeto_id)->orderBy('nome')->get();
        }
    }

    protected $listeners = ['atualizarTodos' => 'atualizarTudo'];

    public function atualizarTudo()
    {
        $this->atualizarClientes();
        $this->atualizarProjetos();
        $this->atualizarServicos();
    }

    public function render()
    {
        return view('livewire.selecionar-diretorio-modal');
    }
} 