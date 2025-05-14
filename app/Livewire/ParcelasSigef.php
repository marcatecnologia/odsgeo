<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ParcelasSigef extends Component
{
    public $estado = '';
    public $municipio = '';
    public $municipios = [];
    public $latitude = null;
    public $longitude = null;
    public $raio = 1000;
    public $activeTab = 'municipio';
    public $coordenadaCentral = null;
    public $sigef = null;
    public $geojson = null;
    public $centroide = null;
    public $erro = null;

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required|string|size:7',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:100|max:10000'
    ];

    public function mount()
    {
        $this->municipios = [];
    }

    public function updatedEstado($value)
    {
        $this->municipio = '';
        $this->municipios = [];
        $this->geojson = null;
        $this->centroide = null;
        $this->erro = null;

        if ($value) {
            $this->carregarMunicipios($value);
        }
    }

    public function updatedMunicipio($value)
    {
        if ($value && $this->geojson) {
            $this->centralizarMunicipio($value);
        }
    }

    protected function carregarMunicipios($uf)
    {
        try {
            // Carrega GeoJSON do estado
            $geojsonPath = base_path("database/geojson/municipios/municipios_{$uf}.geojson");
            if (!file_exists($geojsonPath)) {
                throw new \Exception("Arquivo GeoJSON não encontrado para {$uf}");
            }

            $this->geojson = json_decode(file_get_contents($geojsonPath), true);
            
            // Extrai lista de municípios
            $this->municipios = collect($this->geojson['features'])
                ->map(function ($feature) {
                    return [
                        'codigo' => $feature['properties']['codigo_ibge'],
                        'nome' => $feature['properties']['nome']
                    ];
                })
                ->sortBy('nome')
                ->values()
                ->toArray();

            // Calcula centroide do estado
            $this->calcularCentroideEstado();

        } catch (\Exception $e) {
            $this->erro = "Erro ao carregar municípios: " . $e->getMessage();
            $this->municipios = [];
        }
    }

    protected function calcularCentroideEstado()
    {
        if (!$this->geojson) return;

        $bounds = [PHP_FLOAT_MAX, PHP_FLOAT_MAX, PHP_FLOAT_MIN, PHP_FLOAT_MIN];
        
        foreach ($this->geojson['features'] as $feature) {
            $coordinates = $feature['geometry']['coordinates'][0];
            foreach ($coordinates as $coord) {
                $bounds[0] = min($bounds[0], $coord[0]);
                $bounds[1] = min($bounds[1], $coord[1]);
                $bounds[2] = max($bounds[2], $coord[0]);
                $bounds[3] = max($bounds[3], $coord[1]);
            }
        }

        $this->centroide = [
            'lat' => ($bounds[1] + $bounds[3]) / 2,
            'lng' => ($bounds[0] + $bounds[2]) / 2
        ];
    }

    protected function centralizarMunicipio($codigoMunicipio)
    {
        if (!$this->geojson) return;

        $municipio = collect($this->geojson['features'])
            ->first(function ($feature) use ($codigoMunicipio) {
                return $feature['properties']['codigo_ibge'] == $codigoMunicipio;
            });

        if ($municipio) {
            $coordinates = $municipio['geometry']['coordinates'][0];
            $bounds = [PHP_FLOAT_MAX, PHP_FLOAT_MAX, PHP_FLOAT_MIN, PHP_FLOAT_MIN];
            
            foreach ($coordinates as $coord) {
                $bounds[0] = min($bounds[0], $coord[0]);
                $bounds[1] = min($bounds[1], $coord[1]);
                $bounds[2] = max($bounds[2], $coord[0]);
                $bounds[3] = max($bounds[3], $coord[1]);
            }

            $centroide = [
                'lat' => ($bounds[1] + $bounds[3]) / 2,
                'lng' => ($bounds[0] + $bounds[2]) / 2
            ];

            $this->dispatch('centralizar-mapa', [
                'lat' => $centroide['lat'],
                'lng' => $centroide['lng'],
                'zoom' => 11
            ]);
        }
    }

    public function buscarParcelas()
    {
        $this->validate([
            'estado' => 'required|string|size:2',
            'municipio' => 'required|string|size:7'
        ]);

        try {
            $sigefService = app(SigefWfsService::class);
            $response = $sigefService->getParcelasPorMunicipio($this->municipio);

            if ($response['success']) {
                $this->geojson = $response['data'];
                $this->sigef = null;
                $this->dispatch('parcelasRecebidas', $this->geojson);
            } else {
                $this->sigef = $response['message'];
                $this->geojson = null;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por município', [
                'error' => $e->getMessage(),
                'estado' => $this->estado,
                'municipio' => $this->municipio
            ]);
            $this->sigef = 'Erro ao buscar parcelas. Por favor, tente novamente.';
            $this->geojson = null;
        }
    }

    public function buscarParcelasPorCoordenada()
    {
        $this->validate([
            'estado' => 'required|string|size:2',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'raio' => 'required|numeric|min:100|max:10000'
        ]);

        try {
            $sigefService = app(SigefWfsService::class);
            $response = $sigefService->getParcelasPorCoordenada(
                $this->latitude,
                $this->longitude,
                $this->raio
            );

            if ($response['success']) {
                $this->geojson = $response['data'];
                $this->sigef = null;
                $this->coordenadaCentral = [
                    'lat' => $this->latitude,
                    'lon' => $this->longitude
                ];
                $this->dispatch('parcelasRecebidas', $this->geojson);
            } else {
                $this->sigef = $response['message'];
                $this->geojson = null;
                $this->coordenadaCentral = null;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas por coordenada', [
                'error' => $e->getMessage(),
                'estado' => $this->estado,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'raio' => $this->raio
            ]);
            $this->sigef = 'Erro ao buscar parcelas. Por favor, tente novamente.';
            $this->geojson = null;
            $this->coordenadaCentral = null;
        }
    }

    public function atualizarCoordenadas($lat, $lon)
    {
        $this->latitude = $lat;
        $this->longitude = $lon;
    }

    public function render()
    {
        return view('livewire.parcelas-sigef');
    }
} 