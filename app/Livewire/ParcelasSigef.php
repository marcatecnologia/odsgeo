<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GeoServerService;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ParcelasSigef extends Component
{
    public $estado;
    public $municipio;
    public $municipios = [];
    public $latitude;
    public $longitude;
    public $raio = 1000;
    public $parcelas = [];
    public $erro;
    public $centroide;
    public $zoom = 4;
    public $geoserver;
    public $sigefWfs;

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required|string|size:7',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:100|max:10000'
    ];

    public function mount(GeoServerService $geoserver, SigefWfsService $sigefWfs)
    {
        $this->geoserver = $geoserver;
        $this->sigefWfs = $sigefWfs;
    }

    public function updatedEstado($value)
    {
        if ($value) {
            $this->carregarMunicipios($value);
        }
    }

    public function updatedMunicipio($value)
    {
        \Log::info('updatedMunicipio chamado', ['municipio' => $value]);
        if ($value) {
            $this->centralizarMunicipio($value);
            if ($this->centroide) {
                \Log::info('Disparando evento centroideAtualizado', $this->centroide);
                $this->dispatch('centroideAtualizado', [
                    'lat' => $this->centroide['lat'],
                    'lon' => $this->centroide['lon'],
                    'zoom' => $this->zoom
                ]);
            }
        }
    }

    public function carregarMunicipios($uf)
    {
        try {
            $response = $this->geoserver->getMunicipiosByUF($uf);
            
            if (isset($response['features'])) {
                $this->municipios = collect($response['features'])->map(function ($feature) {
                    return [
                        'codigo' => $feature['properties']['codigo_ibge'],
                        'nome' => $feature['properties']['nome']
                    ];
                })->sortBy('nome')->values()->toArray();
                
                $this->centroide = $this->calcularCentroideEstado($response['features']);
                $this->zoom = 6;
            } else {
                $this->erro = 'Erro ao carregar municípios';
                Log::error('Erro ao carregar municípios', ['response' => $response]);
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao carregar municípios: ' . $e->getMessage();
            Log::error('Erro ao carregar municípios', ['error' => $e->getMessage()]);
        }
    }

    public function calcularCentroideEstado($features)
    {
        if (empty($features)) {
            return null;
        }

        $somaLat = 0;
        $somaLon = 0;
        $count = 0;

        foreach ($features as $feature) {
            if (isset($feature['geometry']['coordinates'])) {
                $coords = $feature['geometry']['coordinates'][0][0];
                foreach ($coords as $coord) {
                    $somaLon += $coord[0];
                    $somaLat += $coord[1];
                    $count++;
                }
            }
        }

        if ($count > 0) {
            return [
                'lat' => $somaLat / $count,
                'lon' => $somaLon / $count
            ];
        }

        return null;
    }

    public function centralizarMunicipio($codigo)
    {
        try {
            \Log::info('centralizarMunicipio chamado', ['codigo' => $codigo]);
            $response = $this->geoserver->getMunicipioByCodigo($codigo);
            
            if (isset($response['features'][0]['geometry']['coordinates'])) {
                $coords = $response['features'][0]['geometry']['coordinates'][0][0];
                $somaLat = 0;
                $somaLon = 0;
                $count = 0;

                foreach ($coords as $coord) {
                    $somaLon += $coord[0];
                    $somaLat += $coord[1];
                    $count++;
                }

                if ($count > 0) {
                    $this->centroide = [
                        'lat' => $somaLat / $count,
                        'lon' => $somaLon / $count
                    ];
                    $this->zoom = 10;
                    \Log::info('Centroide calculado', $this->centroide);
                }
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao centralizar município: ' . $e->getMessage();
            \Log::error('Erro ao centralizar município', ['error' => $e->getMessage()]);
        }
    }

    public function buscarParcelas()
    {
        $this->reset(['parcelas', 'erro']);

        if (!$this->municipio) {
            $this->erro = 'Selecione um município';
            return;
        }

        try {
            $response = $this->sigefWfs->getParcelasByMunicipio($this->municipio);
            
            if (isset($response['features'])) {
                $this->parcelas = $response['features'];
            } else {
                $this->erro = 'Nenhuma parcela encontrada';
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas', ['error' => $e->getMessage()]);
        }
    }

    public function buscarParcelasPorCoordenada()
    {
        $this->reset(['parcelas', 'erro']);

        if (!$this->latitude || !$this->longitude) {
            $this->erro = 'Informe a latitude e longitude';
            return;
        }

        try {
            $response = $this->sigefWfs->getParcelasByCoordenada(
                $this->latitude,
                $this->longitude,
                $this->raio
            );
            
            if (isset($response['features'])) {
                $this->parcelas = $response['features'];
            } else {
                $this->erro = 'Nenhuma parcela encontrada';
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.parcelas-sigef');
    }
} 