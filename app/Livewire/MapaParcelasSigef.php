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
            \Log::debug('Geometry retornada:', ['geometry' => $geometry]);
            
            if (is_array($geometry)) {
                // Se for um array de FeatureCollection, pega o primeiro
                if (isset($geometry[0]['type']) && $geometry[0]['type'] === 'FeatureCollection') {
                    $geometry = $geometry[0];
                }
                
                // Se for uma FeatureCollection, verifica se tem features
                if (isset($geometry['type']) && $geometry['type'] === 'FeatureCollection') {
                    if (!empty($geometry['features'])) {
                        $this->dispatch('estadoSelecionado', json_decode(json_encode($geometry)));
                    } else {
                        $this->error = 'A geometria do estado não contém features.';
                    }
                } else {
                    $this->error = 'Formato de geometria inválido.';
                }
            } else {
                $this->error = 'Não foi possível carregar a geometria do estado.';
            }
        } catch (\Exception $e) {
            $this->error = 'Erro ao carregar dados do estado: ' . $e->getMessage();
            \Log::error('Erro ao carregar dados do estado', [
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
            
            // Buscar geometria do município e emitir evento
            $municipioGeometry = $geoserverService->getMunicipioGeometry($value);
            \Log::debug('GeoJSON do município:', ['geojson' => $municipioGeometry]);
            
            if (isset($municipioGeometry['type']) && $municipioGeometry['type'] === 'FeatureCollection' && !empty($municipioGeometry['features'])) {
                $this->dispatch('municipioSelecionado', json_decode(json_encode($municipioGeometry)));
            } else {
                \Log::error('Geometria do município inválida ou vazia', [
                    'municipio' => $value,
                    'geometry' => $municipioGeometry
                ]);
                $this->error = 'Não foi possível carregar a geometria do município.';
            }

            // Buscar parcelas
            $this->parcelas = $geoserverService->getParcelas($value);
            $this->dispatch('parcelasCarregadas', ['parcelas' => $this->parcelas]);
            
        } catch (\Exception $e) {
            $this->error = 'Erro ao carregar dados do município: ' . $e->getMessage();
            \Log::error('Erro ao carregar dados do município', [
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