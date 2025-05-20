<?php

namespace App\Livewire;

use App\Services\GeoServerService;
use Livewire\Component;
use Livewire\Attributes\On;

class MapaParcelasSigef extends Component
{
    public $estado = '';
    public $municipio = '';
    public $municipios = [];
    public $parcelas = [];
    public $loading = false;
    public $error = null;
    public $estados = [];

    public function mount(GeoServerService $geoserverService)
    {
        $this->estados = $geoserverService->getEstados();
    }

    public function updatedEstado($value)
    {
        if (empty($value)) {
            $this->municipio = '';
            $this->municipios = [];
            $this->parcelas = [];
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            $geoserverService = app(\App\Services\GeoServerService::class);
            $this->municipios = $geoserverService->getMunicipios($value);

            if (empty($this->municipios)) {
                $this->error = 'Nenhum município encontrado para este estado.';
            }

            // Emite evento para atualizar o mapa com a geometria do estado
            $geometry = $geoserverService->getEstadoGeometry($value);
            if ($geometry) {
                $this->dispatch('estadoSelecionado', ['geometry' => $geometry]);
            }
        } catch (\Exception $e) {
            $this->error = 'Erro ao carregar municípios: ' . $e->getMessage();
            \Log::error('Erro ao carregar municípios', [
                'estado' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function updatedMunicipio($value)
    {
        if (empty($value)) {
            $this->parcelas = [];
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            $geoserverService = app(GeoServerService::class);
            $this->parcelas = $geoserverService->getParcelas($value);
            
            // Emite evento para atualizar o mapa com as parcelas
            $this->dispatch('parcelasCarregadas', ['parcelas' => $this->parcelas]);
        } catch (\Exception $e) {
            $this->error = 'Erro ao carregar parcelas: ' . $e->getMessage();
            \Log::error('Erro ao carregar parcelas', [
                'municipio' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.mapa-parcelas-sigef');
    }
} 