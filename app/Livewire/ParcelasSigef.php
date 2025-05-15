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
    public $loading = false;
    public $codigoImovel;
    public $matriculaSigef;
    public $ultimaAtualizacao;
    public $activeTab = 'municipio';

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required|string|size:7',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:100|max:10000',
        'codigoImovel' => 'nullable|string|max:20',
        'matriculaSigef' => 'nullable|string|max:20'
    ];

    public function mount()
    {
        $this->activeTab = 'municipio';
    }

    public function updatedEstado($value)
    {
        if ($value) {
            $this->carregarMunicipios($value, app(GeoServerService::class));
        }
    }

    public function updatedMunicipio($value)
    {
        \Log::info('updatedMunicipio chamado', ['municipio' => $value]);
        if ($value) {
            $this->centralizarMunicipio($value, app(GeoServerService::class));
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

    public function carregarMunicipios($uf, GeoServerService $geoserver)
    {
        try {
            $response = $geoserver->getMunicipiosByUF($uf);
            
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

    public function centralizarMunicipio($codigo, GeoServerService $geoserver)
    {
        try {
            \Log::info('centralizarMunicipio chamado', ['codigo' => $codigo]);
            $response = $geoserver->getMunicipioByCodigo($codigo);
            
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

    public function buscarParcelas(SigefWfsService $sigefWfs)
    {
        $this->reset(['parcelas', 'erro']);

        if (!$this->municipio) {
            $this->erro = 'Selecione um município';
            return;
        }

        try {
            $response = $sigefWfs->getParcelasByMunicipio($this->municipio);
            
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

    public function buscarParcelasPorCoordenada(SigefWfsService $sigefWfs)
    {
        $this->reset(['parcelas', 'erro']);

        if (!$this->latitude || !$this->longitude) {
            $this->erro = 'Informe a latitude e longitude';
            return;
        }

        try {
            $response = $sigefWfs->getParcelasByCoordenada(
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

    public function recarregarMunicipios(GeoServerService $geoserver)
    {
        $this->loading = true;
        Cache::forget("municipios_{$this->estado}");
        $this->carregarMunicipios($this->estado, $geoserver);
        $this->loading = false;
    }

    public function recarregarParcelas(SigefWfsService $sigefWfs)
    {
        $this->loading = true;
        Cache::forget("parcelas_{$this->municipio}");
        $this->buscarParcelas($sigefWfs);
        $this->loading = false;
    }

    public function buscarPorCodigo(SigefWfsService $sigefWfs)
    {
        $this->loading = true;
        $this->reset(['parcelas', 'erro']);

        try {
            $response = $sigefWfs->getParcelasByCodigo(
                $this->codigoImovel,
                $this->matriculaSigef
            );
            
            if (isset($response['features']) && !empty($response['features'])) {
                $this->parcelas = $response['features'];
                $this->centralizarParcela($response['features'][0]);
            } else {
                $this->erro = 'Nenhuma parcela encontrada com os critérios informados';
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcela: ' . $e->getMessage();
            Log::error('Erro ao buscar parcela por código', [
                'error' => $e->getMessage(),
                'codigo' => $this->codigoImovel,
                'matricula' => $this->matriculaSigef
            ]);
        }

        $this->loading = false;
    }

    protected function centralizarParcela($parcela)
    {
        if (isset($parcela['geometry']['coordinates'])) {
            $coords = $parcela['geometry']['coordinates'][0][0];
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
                $this->zoom = 15;
                $this->dispatch('centroideAtualizado', [
                    'lat' => $this->centroide['lat'],
                    'lon' => $this->centroide['lon'],
                    'zoom' => $this->zoom
                ]);
            }
        }
    }

    public function novaBusca()
    {
        $this->reset([
            'parcelas',
            'erro',
            'codigoImovel',
            'matriculaSigef',
            'latitude',
            'longitude'
        ]);
    }

    public function render()
    {
        return view('livewire.parcelas-sigef');
    }
} 