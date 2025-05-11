<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Servico;

class SelecionarDiretorioButton extends Component
{
    public $currentService = null;

    public function mount()
    {
        $this->loadCurrentService();
    }

    public function loadCurrentService()
    {
        $currentServiceId = session('current_service_id');
        if ($currentServiceId) {
            $this->currentService = Servico::with(['projeto.cliente'])->find($currentServiceId);
        }
    }

    public function render()
    {
        return view('livewire.selecionar-diretorio-button');
    }
} 