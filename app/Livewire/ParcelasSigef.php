<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GeoServerParcelasService;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class ParcelasSigef extends Component
{
    public $estado = null;
    public $municipio = null;
    public $municipios = [];
    public $latitude;
    public $longitude;
    public $raio = 1000;
    public $parcelas = [];
    public $erro = null;
    public $centroide;
    public $zoom = 4;
    public $loading = false;
    public $codigoImovel;
    public $ccir;
    public $cnpj;
    public $nomePropriedade;
    public $activeTab = 'municipio';
    public $currentPage = 1;
    public $totalPages = 1;
    public $totalParcelas = 0;
    public $perPage = 50;
    public $estados = [
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

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required|string',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:1|max:10000',
        'codigoImovel' => 'required|string|size:13',
        'ccir' => 'required|string|size:14',
        'cnpj' => 'required|string|size:14',
        'nomePropriedade' => 'required|string|min:3'
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
        'raio.min' => 'O raio mínimo é 1 metro',
        'raio.max' => 'O raio máximo é 10.000 metros',
        'codigoImovel.required' => 'O código do imóvel é obrigatório',
        'codigoImovel.size' => 'O código do imóvel deve ter 13 dígitos',
        'ccir.required' => 'O número do CCIR é obrigatório',
        'ccir.size' => 'O número do CCIR deve ter 14 dígitos',
        'cnpj.required' => 'O CNPJ é obrigatório',
        'cnpj.size' => 'O CNPJ deve ter 14 dígitos',
        'nomePropriedade.required' => 'O nome da propriedade é obrigatório',
        'nomePropriedade.min' => 'O nome da propriedade deve ter no mínimo 3 caracteres'
    ];

    protected GeoServerParcelasService $geoserverService;

    public function boot(GeoServerParcelasService $geoserverService): void
    {
        $this->geoserverService = $geoserverService;
    }

    public function mount(): void
    {
        $this->perPage = config('geoserver.pagination.per_page', 50);
        $this->raio = config('geoserver.coordenada.raio_padrao', 1000);
    }

    public function updatedEstado($value)
    {
        if ($value) {
            $this->loading = true;
            try {
                $result = $this->geoserverService->getMunicipiosPorUF($value);
                if ($result['success']) {
                    $this->municipios = collect($result['municipios'])->mapWithKeys(function ($municipio) {
                        return [$municipio['codigo'] => $municipio['nome']];
                    })->toArray();
                    $this->reset(['municipio', 'parcelas', 'erro', 'currentPage']);
                } else {
                    $this->erro = $result['error'];
                }
            } catch (\Exception $e) {
                $this->erro = 'Erro ao carregar municípios: ' . $e->getMessage();
                Log::error('Erro ao carregar municípios', [
                    'error' => $e->getMessage(),
                    'uf' => $value
                ]);
            } finally {
                $this->loading = false;
            }
        }
    }

    public function updatedMunicipio($value)
    {
        if ($value) {
            $this->loading = true;
            try {
                $geoserverService = app(\App\Services\GeoServerService::class);
                $municipioGeometry = $geoserverService->getMunicipioGeometry($value);
                
                if ($municipioGeometry) {
                    $this->dispatch('municipioSelecionado', $municipioGeometry);
                } else {
                    $this->erro = 'Não foi possível obter a geometria do município';
                }
                
                $this->reset(['parcelas', 'erro', 'currentPage']);
            } catch (\Exception $e) {
                $this->erro = 'Erro ao centralizar município: ' . $e->getMessage();
                Log::error('Erro ao centralizar município', [
                    'error' => $e->getMessage(),
                    'municipio' => $value
                ]);
            } finally {
                $this->loading = false;
            }
        }
    }

    public function buscarParcelas()
    {
        $this->validate([
            'estado' => 'required|string|size:2',
            'municipio' => 'required|string'
        ]);

        $this->loading = true;
        $this->erro = null;

        try {
            $result = $this->geoserverService->getParcelasPorMunicipio($this->municipio, $this->currentPage, $this->perPage);
            
            if ($result['success']) {
                $this->parcelas = $result['parcelas'];
                $this->totalParcelas = $result['total'];
                $this->totalPages = ceil($result['total'] / $this->perPage);
                
                $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
            } else {
                $this->erro = $result['error'];
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas', [
                'error' => $e->getMessage(),
                'municipio' => $this->municipio,
                'estado' => $this->estado
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function buscarParcelasPorCoordenada()
    {
        $this->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'raio' => 'required|numeric|min:1|max:10000'
        ]);

        $this->loading = true;
        $this->erro = null;

        try {
            $result = $this->geoserverService->getParcelasPorCoordenada(
                floatval($this->latitude),
                floatval($this->longitude),
                floatval($this->raio)
            );
            
            if ($result['success']) {
                $this->parcelas = $result['parcelas'];
                $this->totalParcelas = $result['total'];
                $this->totalPages = 1;
                
                $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
            } else {
                $this->erro = $result['error'];
            }
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

    public function buscarPorCodigo()
    {
        $this->validate([
            'codigoImovel' => 'required|string|size:13'
        ]);

        $this->loading = true;
        $this->erro = null;

        try {
            $result = $this->geoserverService->getParcelasPorCodigo($this->codigoImovel);
            
            if ($result['success']) {
                $this->parcelas = $result['parcelas'];
                $this->totalParcelas = $result['total'];
                $this->totalPages = 1;
                
                $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
            } else {
                $this->erro = $result['error'];
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas por código', [
                'error' => $e->getMessage(),
                'codigo' => $this->codigoImovel
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function buscarPorCCIR()
    {
        $this->validate([
            'ccir' => 'required|string|size:14'
        ]);

        $this->loading = true;
        $this->erro = null;

        try {
            $result = $this->geoserverService->getParcelasPorCCIR($this->ccir);
            
            if ($result['success']) {
                $this->parcelas = $result['parcelas'];
                $this->totalParcelas = $result['total'];
                $this->totalPages = 1;
                
                $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
            } else {
                $this->erro = $result['error'];
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas por CCIR', [
                'error' => $e->getMessage(),
                'ccir' => $this->ccir
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function buscarPorCNPJ()
    {
        $this->validate([
            'cnpj' => 'required|string|size:14'
        ]);

        $this->loading = true;
        $this->erro = null;

        try {
            $result = $this->geoserverService->getParcelasPorCNPJ($this->cnpj);
            
            if ($result['success']) {
                $this->parcelas = $result['parcelas'];
                $this->totalParcelas = $result['total'];
                $this->totalPages = 1;
                
                $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
            } else {
                $this->erro = $result['error'];
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas por CNPJ', [
                'error' => $e->getMessage(),
                'cnpj' => $this->cnpj
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function buscarPorNome()
    {
        $this->validate([
            'nomePropriedade' => 'required|string|min:3'
        ]);

        $this->loading = true;
        $this->erro = null;

        try {
            $result = $this->geoserverService->getParcelasPorNome($this->nomePropriedade);
            
            if ($result['success']) {
                $this->parcelas = $result['parcelas'];
                $this->totalParcelas = $result['total'];
                $this->totalPages = ceil($this->totalParcelas / $this->perPage);
                
                $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
            } else {
                $this->erro = $result['error'];
            }
        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar parcelas: ' . $e->getMessage();
            Log::error('Erro ao buscar parcelas por nome', [
                'error' => $e->getMessage(),
                'nome' => $this->nomePropriedade
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function nextPage()
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->buscarParcelas();
        }
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->buscarParcelas();
        }
    }

    public function gotoPage($page)
    {
        if ($page >= 1 && $page <= $this->totalPages) {
            $this->currentPage = $page;
            $this->buscarParcelas();
        }
    }

    protected function calcularCentroide($geometry)
    {
        if (!isset($geometry['coordinates'])) {
            return null;
        }

        $coords = $geometry['coordinates'][0][0];
        $somaLat = 0;
        $somaLon = 0;
        $count = 0;

        foreach ($coords as $coord) {
            $somaLon += $coord[0];
            $somaLat += $coord[1];
            $count++;
        }

        if ($count > 0) {
            return [
                'lat' => $somaLat / $count,
                'lon' => $somaLon / $count
            ];
        }

        return null;
    }

    public function novaBusca()
    {
        $this->reset([
            'parcelas',
            'erro',
            'codigoImovel',
            'ccir',
            'cnpj',
            'nomePropriedade',
            'latitude',
            'longitude',
            'currentPage'
        ]);
    }

    public function render()
    {
        return view('livewire.parcelas-sigef');
    }
} 