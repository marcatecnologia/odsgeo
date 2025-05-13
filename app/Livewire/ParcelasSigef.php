<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Log;

class ParcelasSigef extends Component
{
    public $estado = '';
    public $municipio = '';
    public $latitude = null;
    public $longitude = null;
    public $raio = 1000;
    public $activeTab = 'municipio';
    public $coordenadaCentral = null;
    public $sigef = null;
    public $geojson = null;

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required|string|size:7',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:100|max:10000'
    ];

    public function mount()
    {
        $this->estados = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];

        $this->municipios = [];
    }

    public function updatedEstado($value)
    {
        if (strlen($value) === 2) {
            $this->municipios = $this->getMunicipios($value);
        } else {
            $this->municipios = [];
        }
        $this->municipio = '';
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

    private function getMunicipios($uf)
    {
        // Aqui você pode implementar a lógica para buscar os municípios do estado
        // Por enquanto, retornando um array vazio
        return [];
    }

    public function render()
    {
        return view('livewire.parcelas-sigef');
    }
} 