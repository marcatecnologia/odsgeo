<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GeoServerParcelasService;
use Illuminate\Support\Facades\Log;

class MapaParcelasSigef extends Component
{
    public $estadoSelecionado = '';
    public $municipioSelecionado = '';
    public $estados = [];
    public $municipios = [];
    public $parcelas = [];
    public $loading = false;
    public $error = null;

    protected $listeners = [
        'estadoSelecionado' => 'handleEstadoSelecionado',
        'municipioSelecionado' => 'handleMunicipioSelecionado'
    ];

    public function mount()
    {
        Log::info('Livewire mount chamado');
        $this->carregarEstados();
    }

    public function updatedEstadoSelecionado($value)
    {
        Log::info('Estado selecionado via Livewire:', ['value' => $value]);
        $this->carregarMunicipios();
        $this->municipioSelecionado = '';
        $this->error = null;
    }

    public function carregarEstados()
    {
        $service = app(GeoServerParcelasService::class);
        try {
            $this->estados = $service->getEstados();
            if (empty($this->estados)) {
                $this->error = 'Nenhum estado encontrado no GeoServer. Verifique a configuração ou disponibilidade do serviço.';
                Log::error('GeoServer retornou lista vazia de estados em br_uf_2024');
            }
        } catch (\Exception $e) {
            $this->error = 'Erro ao buscar estados no GeoServer: ' . $e->getMessage();
            Log::error('Erro ao buscar estados no GeoServer', ['exception' => $e]);
        }
    }

    public function handleEstadoSelecionado($estado)
    {
        $this->estadoSelecionado = $estado;
        $this->municipioSelecionado = null;
        $this->carregarMunicipios();
        $this->dispatch('zoomToEstado', ['estado' => $estado]);
    }

    public function handleMunicipioSelecionado($municipio)
    {
        $this->municipioSelecionado = $municipio;
        $this->carregarParcelas();
        $this->dispatch('zoomToMunicipio', ['municipio' => $municipio]);
    }

    public function carregarMunicipios()
    {
        if (!$this->estadoSelecionado) {
            $this->municipios = [];
            return;
        }
        $service = app(GeoServerParcelasService::class);
        try {
            $this->municipios = $service->getMunicipiosPorUF($this->estadoSelecionado);
            if (empty($this->municipios)) {
                $this->error = 'Nenhum município encontrado para este estado no GeoServer.';
                Log::error('GeoServer retornou lista vazia de municípios para o estado', ['uf' => $this->estadoSelecionado]);
            }
        } catch (\Exception $e) {
            $this->error = 'Erro ao buscar municípios no GeoServer: ' . $e->getMessage();
            Log::error('Erro ao buscar municípios no GeoServer', ['exception' => $e, 'uf' => $this->estadoSelecionado]);
        }
    }

    public function carregarParcelas()
    {
        if (!$this->municipioSelecionado) {
            $this->parcelas = [];
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            $service = app(GeoServerParcelasService::class);
            $this->parcelas = $service->getParcelasPorMunicipio($this->municipioSelecionado);
        } catch (\Exception $e) {
            $this->error = 'Erro ao carregar parcelas: ' . $e->getMessage();
            $this->parcelas = [];
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.mapa-parcelas-sigef');
    }
} 