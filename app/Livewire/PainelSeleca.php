<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\Servico;

class PainelSeleca extends Component
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
        if ($this->cliente_id) {
            $this->projetos = Projeto::where('cliente_id', $this->cliente_id)->orderBy('nome')->get();
        }
        if ($this->projeto_id) {
            $this->servicos = Servico::where('projeto_id', $this->projeto_id)->orderBy('nome')->get();
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

    public function render()
    {
        return view('livewire.painel-seleca');
    }
}
