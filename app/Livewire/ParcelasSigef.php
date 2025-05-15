<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GeoServerService;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class ParcelasSigef extends Component
{
    public $estado;
    public $municipio;
    public $municipios = [];
    public $latitude;
    public $longitude;
    public $raio;
    public $parcelas = [];
    public $erro;
    public $centroide;
    public $zoom = 4;
    public $loading = false;
    public $codigoImovel;
    public $matriculaSigef;
    public $ultimaAtualizacao;
    public $activeTab = 'municipio';
    public $currentPage = 1;
    public $totalPages = 1;
    public $totalParcelas = 0;
    public $areaMinima;
    public $areaMaxima;

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required|string|size:7',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:100|max:10000',
        'codigoImovel' => 'nullable|string|max:20',
        'matriculaSigef' => 'nullable|string|max:20',
        'areaMinima' => 'nullable|numeric|min:0',
        'areaMaxima' => 'nullable|numeric|min:0|gt:areaMinima'
    ];

    protected $messages = [
        'estado.required' => 'Selecione o estado',
        'municipio.required' => 'Selecione o município',
        'latitude.required' => 'A latitude é obrigatória',
        'latitude.numeric' => 'A latitude deve ser um número',
        'latitude.between' => 'A latitude deve estar entre -90 e 90',
        'longitude.required' => 'A longitude é obrigatória',
        'longitude.numeric' => 'A longitude deve ser um número',
        'longitude.between' => 'A longitude deve estar entre -180 e 180',
        'raio.required' => 'O raio é obrigatório',
        'raio.numeric' => 'O raio deve ser um número',
        'raio.min' => 'O raio mínimo é 100 metros',
        'raio.max' => 'O raio máximo é 10.000 metros',
        'areaMinima.numeric' => 'A área mínima deve ser um número',
        'areaMinima.min' => 'A área mínima não pode ser negativa',
        'areaMaxima.numeric' => 'A área máxima deve ser um número',
        'areaMaxima.min' => 'A área máxima não pode ser negativa',
        'areaMaxima.gt' => 'A área máxima deve ser maior que a área mínima'
    ];

    public function mount()
    {
        $this->activeTab = 'municipio';
        $this->raio = config('sigef.coordenada.raio_padrao', 1000);
    }

    public function updatedEstado($value)
    {
        if ($value) {
            $this->carregarMunicipios($value, app(GeoServerService::class));
        }
    }

    public function updatedMunicipio($value)
    {
        if ($value) {
            $this->centralizarMunicipio($value, app(GeoServerService::class));
            if ($this->centroide) {
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
        $this->validate([
            'estado' => 'required|string|size:2',
            'municipio' => 'required|string|size:7',
            'areaMinima' => 'nullable|numeric|min:0',
            'areaMaxima' => 'nullable|numeric|min:0|gt:areaMinima'
        ]);

        $this->reset(['parcelas', 'erro', 'currentPage']);
        $this->loading = true;

        try {
            $result = $sigefWfs->getParcelasPorMunicipio(
                $this->estado,
                $this->municipio,
                $this->currentPage
            );

            if (!$result['success']) {
                $this->erro = $result['error'];
                return;
            }

            if (!$result['has_data']) {
                $this->erro = 'Nenhuma parcela encontrada para este município.';
                return;
            }

            $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
            $this->totalParcelas = $result['pagination']['total'] ?? 0;
            $this->totalPages = ceil($this->totalParcelas / config('sigef.pagination.per_page', 50));

            // Filtra por área se necessário
            if ($this->areaMinima || $this->areaMaxima) {
                $this->parcelas = collect($this->parcelas)->filter(function ($parcela) {
                    $area = $parcela['properties']['area_ha'] ?? 0;
                    if ($this->areaMinima && $area < $this->areaMinima) {
                        return false;
                    }
                    if ($this->areaMaxima && $area > $this->areaMaxima) {
                        return false;
                    }
                    return true;
                })->values()->toArray();
            }

            $this->dispatch('parcelasRecebidas', [
                'parcelas' => $this->parcelas,
                'pagination' => [
                    'current_page' => $this->currentPage,
                    'total_pages' => $this->totalPages,
                    'total' => $this->totalParcelas
                ]
            ]);

        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas', [
                'error' => $e->getMessage(),
                'estado' => $this->estado,
                'municipio' => $this->municipio
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function buscarParcelasPorCoordenada(SigefWfsService $sigefWfs)
    {
        $this->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'raio' => 'required|numeric|min:100|max:10000',
            'estado' => 'required|string|size:2'
        ]);

        $this->reset(['parcelas', 'erro']);
        $this->loading = true;

        try {
            $result = $sigefWfs->buscarParcelasPorCoordenada(
                floatval($this->latitude),
                floatval($this->longitude),
                floatval($this->raio),
                $this->estado
            );

            if (!$result['success']) {
                $this->erro = $result['error'];
                return;
            }

            if (!$result['has_data']) {
                $this->erro = 'Nenhuma parcela encontrada para os parâmetros informados.';
                return;
            }

            $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
            
            // Filtra por área se necessário
            if ($this->areaMinima || $this->areaMaxima) {
                $this->parcelas = collect($this->parcelas)->filter(function ($parcela) {
                    $area = $parcela['properties']['area_ha'] ?? 0;
                    if ($this->areaMinima && $area < $this->areaMinima) {
                        return false;
                    }
                    if ($this->areaMaxima && $area > $this->areaMaxima) {
                        return false;
                    }
                    return true;
                })->values()->toArray();
            }

            $this->dispatch('parcelasRecebidas', [
                'parcelas' => $this->parcelas,
                'bbox' => $result['bbox']
            ]);

        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas por coordenada', [
                'error' => $e->getMessage(),
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'raio' => $this->raio
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function buscarPorCodigo(SigefWfsService $sigefWfs)
    {
        $this->validate([
            'codigoImovel' => 'required_without:matriculaSigef|string|max:20',
            'matriculaSigef' => 'required_without:codigoImovel|string|max:20'
        ]);

        $this->reset(['parcelas', 'erro']);
        $this->loading = true;

        try {
            $result = $sigefWfs->getParcelasByCodigo(
                $this->codigoImovel,
                $this->matriculaSigef
            );

            if (!$result['success']) {
                $this->erro = $result['error'];
                return;
            }

            if (!$result['has_data']) {
                $this->erro = 'Nenhuma parcela encontrada com os critérios informados.';
                return;
            }

            $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
            $this->centralizarParcela($this->parcelas[0] ?? null);

        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcela: ' . $e->getMessage();
            Log::error('Erro ao buscar parcela por código', [
                'error' => $e->getMessage(),
                'codigo' => $this->codigoImovel,
                'matricula' => $this->matriculaSigef
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function nextPage()
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->buscarParcelas(app(SigefWfsService::class));
        }
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->buscarParcelas(app(SigefWfsService::class));
        }
    }

    public function gotoPage($page)
    {
        if ($page >= 1 && $page <= $this->totalPages) {
            $this->currentPage = $page;
            $this->buscarParcelas(app(SigefWfsService::class));
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
            'longitude',
            'areaMinima',
            'areaMaxima',
            'currentPage'
        ]);
    }

    public function render()
    {
        return view('livewire.parcelas-sigef');
    }
} 