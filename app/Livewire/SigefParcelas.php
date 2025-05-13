<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SigefWfsService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class SigefParcelas extends Component
{
    public $estados = [];
    public $municipios = [];
    public $municipioCentroides = [];
    public $estado = '';
    public $municipio = '';
    public $latitude = '';
    public $longitude = '';
    public $raio = 1000;
    public $parcelasGeojson = null;

    protected $rules = [
        'estado' => 'required|string|size:2',
        'municipio' => 'required',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'raio' => 'required|numeric|min:1|max:10000',
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
    ];

    public function mount()
    {
        $this->estados = $this->getEstados();
        $this->municipios = [];
        $this->municipioCentroides = [];
        $this->parcelasGeojson = null;
    }

    public function updatedEstado($uf)
    {
        $this->municipio = '';
        $this->municipios = $this->getMunicipios($uf);
        $this->municipioCentroides = $this->getMunicipioCentroides($uf);
    }

    public function updatedMunicipio($codigo)
    {
        if ($codigo && isset($this->municipioCentroides[$codigo])) {
            $centroide = $this->municipioCentroides[$codigo];
            $this->dispatch('municipioCentralizado', $centroide);
        }
    }

    public function buscarParcelasPorCoordenada()
    {
        $this->validate();
        $this->parcelasGeojson = null;
        try {
            $sigefService = app(SigefWfsService::class);
            $result = $sigefService->buscarParcelasPorCoordenada(
                floatval($this->latitude),
                floatval($this->longitude),
                floatval($this->raio),
                $this->estado
            );
            if (isset($result['success']) && $result['success'] && isset($result['data'])) {
                $geojson = json_decode($result['data'], true);
                $this->parcelasGeojson = $geojson;
                $this->dispatch('parcelasGeojsonAtualizadas', $geojson);
                if (empty($geojson['features'])) {
                    session()->flash('info', 'Nenhuma parcela encontrada para os parâmetros informados.');
                }
            } else {
                $msg = $result['error'] ?? 'Erro ao buscar parcelas.';
                session()->flash('info', $msg);
            }
        } catch (\Exception $e) {
            session()->flash('info', 'Erro ao buscar parcelas: ' . $e->getMessage());
        }
    }

    // Helpers para estados, municípios e centroides
    public function getEstados()
    {
        return [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
            'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
            'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
            'SE' => 'Sergipe', 'TO' => 'Tocantins',
        ];
    }

    public function getMunicipios($uf)
    {
        $this->atualizarBaseMunicipios($uf);
        $path = base_path("database/municipios/{$uf}.json");
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path), true);
            return $json ? array_column($json, 'nome', 'codigo_ibge') : [];
        }
        return [];
    }

    public function getMunicipioCentroides($uf)
    {
        $this->atualizarBaseMunicipios($uf);
        $path = base_path("database/municipios/{$uf}.json");
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path), true);
            $centroides = [];
            foreach ($json as $item) {
                $centroides[$item['codigo_ibge']] = [
                    'lat' => $item['lat'],
                    'lng' => $item['lng'],
                ];
            }
            return $centroides;
        }
        return [];
    }

    /**
     * Atualiza a base local de municípios de um estado se o arquivo não existir ou estiver desatualizado (15 dias).
     */
    public function atualizarBaseMunicipios($uf)
    {
        $dir = base_path('database/municipios');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = "$dir/{$uf}.json";
        $precisaAtualizar = true;
        if (file_exists($file)) {
            $modificado = filemtime($file);
            if ($modificado && (time() - $modificado) < 60 * 60 * 24 * 15) {
                $precisaAtualizar = false;
            }
        }
        if ($precisaAtualizar) {
            $response = \Http::get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios");
            if ($response->ok()) {
                $arr = $response->json();
                $municipios = [];
                foreach ($arr as $m) {
                    // Buscar centroide (API v2)
                    $centroide = null;
                    $centroideResp = \Http::get("https://servicodados.ibge.gov.br/api/v2/municipios/{$m['id']}");
                    if ($centroideResp->ok()) {
                        $data = $centroideResp->json();
                        if (isset($data[0]['centroide'])) {
                            $centroide = $data[0]['centroide'];
                        }
                    }
                    $municipios[] = [
                        'codigo_ibge' => $m['id'],
                        'nome' => $m['nome'],
                        'lat' => $centroide['latitude'] ?? null,
                        'lng' => $centroide['longitude'] ?? null,
                    ];
                }
                file_put_contents($file, json_encode($municipios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            }
        }
    }

    public function render()
    {
        return view('livewire.sigef-parcelas');
    }
} 