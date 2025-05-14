<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GeoServerService;
use Illuminate\Support\Facades\Log;

class MapaMunicipios extends Component
{
    public $ufSelecionada = null;
    public $municipioSelecionado = null;
    public $municipios = [];
    public $erro = null;
    public $carregando = false;
    public $centroideEstado = null;

    protected $geoserver;

    public function mount(GeoServerService $geoserver)
    {
        $this->geoserver = $geoserver;
    }

    public function carregarMunicipios($uf)
    {
        $this->ufSelecionada = $uf;
        $this->carregando = true;
        $this->erro = null;
        $this->municipios = [];
        $this->municipioSelecionado = null;

        try {
            $response = $this->geoserver->getMunicipiosByUF($uf);
            
            if (isset($response['features'])) {
                $this->municipios = collect($response['features'])->map(function ($feature) {
                    return [
                        'codigo' => $feature['properties']['codigo_ibge'],
                        'nome' => $feature['properties']['nome'],
                        'geometry' => $feature['geometry'],
                        'centroide' => $feature['properties']['centroide']
                    ];
                })->sortBy('nome')->values()->toArray();

                // Calcular centróide do estado
                if (!empty($this->municipios)) {
                    $centroides = collect($this->municipios)->pluck('centroide');
                    $lats = $centroides->pluck(1);
                    $lngs = $centroides->pluck(0);
                    
                    $this->centroideEstado = [
                        'lat' => ($lats->min() + $lats->max()) / 2,
                        'lng' => ($lngs->min() + $lngs->max()) / 2
                    ];
                }

                $this->dispatch('municipiosCarregados', [
                    'municipios' => $this->municipios,
                    'centroideEstado' => $this->centroideEstado
                ]);
            } else {
                $this->erro = 'Nenhum município encontrado para esta UF.';
            }
        } catch (\Exception $e) {
            Log::error('Erro ao carregar municípios', [
                'uf' => $uf,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->erro = 'Erro ao carregar os municípios. Por favor, tente novamente.';
        }

        $this->carregando = false;
    }

    public function selecionarMunicipio($codigo)
    {
        $this->municipioSelecionado = $codigo;
        
        $municipio = collect($this->municipios)->first(function ($m) use ($codigo) {
            return $m['codigo'] === $codigo;
        });

        if ($municipio) {
            $this->dispatch('municipioSelecionado', [
                'municipio' => $municipio
            ]);
        }
    }

    public function render()
    {
        return view('livewire.mapa-municipios');
    }
} 