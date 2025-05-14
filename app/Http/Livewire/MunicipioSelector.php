<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MunicipioSelector extends Component
{
    public $uf = '';
    public $municipio = '';
    public $municipios = [];
    public $centroide = null;
    public $error = null;

    protected $listeners = ['ufSelected' => 'handleUfSelected'];

    public function mount()
    {
        // Inicializar com UF padrão se necessário
    }

    public function handleUfSelected($uf)
    {
        $this->uf = $uf;
        $this->municipio = '';
        $this->municipios = [];
        $this->centroide = null;
        $this->error = null;

        try {
            // Buscar municípios do banco de dados
            $municipios = DB::table('municipios_simplificado')
                ->where('uf', $uf)
                ->get();

            if ($municipios->isNotEmpty()) {
                $this->municipios = $municipios->map(function ($municipio) {
                    return [
                        'codigo_ibge' => $municipio->codigo_ibge,
                        'nome' => $municipio->nome,
                        'geometry' => json_decode($municipio->geom_simplificado, true)
                    ];
                })->sortBy('nome')->values()->toArray();

                // Calcular centróide do estado
                $this->calculateStateCentroid();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao carregar municípios: ' . $e->getMessage());
            $this->error = 'Erro ao carregar municípios. Por favor, tente novamente.';
        }
    }

    public function handleMunicipioSelected($codigoIbge)
    {
        $this->municipio = $codigoIbge;
        
        // Encontrar município selecionado
        $municipio = collect($this->municipios)->firstWhere('codigo_ibge', $codigoIbge);
        
        if ($municipio) {
            // Usar centróide do município
            $centroide = DB::table('municipios_simplificado')
                ->where('codigo_ibge', $codigoIbge)
                ->value('centroide');

            if ($centroide) {
                $coords = json_decode($centroide, true);
                $this->centroide = [
                    'lat' => $coords['coordinates'][1],
                    'lng' => $coords['coordinates'][0]
                ];
            }
        }
    }

    private function calculateStateCentroid()
    {
        try {
            // Buscar centróide do estado
            $centroide = DB::table('municipios_simplificado')
                ->where('uf', $this->uf)
                ->selectRaw('AVG(JSON_EXTRACT(centroide, "$.coordinates[0]")) as lng, AVG(JSON_EXTRACT(centroide, "$.coordinates[1]")) as lat')
                ->first();

            if ($centroide) {
                $this->centroide = [
                    'lat' => $centroide->lat,
                    'lng' => $centroide->lng
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erro ao calcular centróide do estado: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.municipio-selector');
    }
} 