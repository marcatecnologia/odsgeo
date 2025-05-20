<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Livewire\MapaParcelasSigef;

class ParcelasSigef extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Parcelas SIGEF';
    protected static ?string $title = 'Visualização de Parcelas SIGEF';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.parcelas-sigef';
    protected static ?string $slug = 'parcelas-sigef';

    public ?array $data = [];
    public ?array $searchResults = null;
    public ?array $geojson = null;
    public bool $isSearching = false;

    protected SigefWfsService $wfsService;

    public function boot(SigefWfsService $wfsService): void
    {
        $this->wfsService = $wfsService;
    }

    public function mount()
    {
        // Removido: $this->authorize('viewAny', \App\Models\ParcelaSigef::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('estado')
                    ->label('Estado')
                    ->options([
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
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            $this->loadMunicipios($state);
                        }
                    }),

                Select::make('municipio')
                    ->label('Município')
                    ->options(function () {
                        $estado = $this->data['estado'] ?? null;
                        if (!$estado) {
                            return [];
                        }
                        return $this->getMunicipios($estado);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            $this->buscarParcelas();
                        }
                    }),
            ])
            ->statePath('data');
    }

    public function loadMunicipios(string $estado): void
    {
        if (empty($estado)) {
            return;
        }

        $cacheKey = "municipios_{$estado}";
        
        if (!Cache::has($cacheKey)) {
            try {
                $response = $this->wfsService->getFeature('municipios', [
                    'CQL_FILTER' => "uf = '{$estado}'"
                ]);

                if ($response['success']) {
                    $data = json_decode($response['data'], true);
                    
                    if (isset($data['features']) && is_array($data['features'])) {
                        $municipios = collect($data['features'])
                            ->pluck('properties.nome', 'properties.codigo')
                            ->toArray();
                        
                        Cache::put($cacheKey, $municipios, 3600);
                    } else {
                        Log::error('Formato de resposta inválido para municípios', [
                            'estado' => $estado,
                            'response' => $response['data']
                        ]);
                        
                        Notification::make()
                            ->title('Erro ao carregar municípios')
                            ->body('Formato de resposta inválido do serviço.')
                            ->danger()
                            ->send();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erro ao carregar municípios', [
                    'estado' => $estado,
                    'error' => $e->getMessage()
                ]);

                Notification::make()
                    ->title('Erro ao carregar municípios')
                    ->body('Não foi possível carregar a lista de municípios. Tente novamente em alguns minutos.')
                    ->danger()
                    ->send();
            }
        }
    }

    private function getMunicipios(string $estado): array
    {
        $cacheKey = "municipios_{$estado}";
        return Cache::get($cacheKey, []);
    }

    public function buscarParcelas()
    {
        $this->validate([
            'data.estado' => 'required|string|size:2',
            'data.municipio' => 'required|string|size:7'
        ]);

        try {
            $result = $this->wfsService->getParcelasPorMunicipio($this->data['estado'], $this->data['municipio']);

            if (!$result['success']) {
                $this->addError('sigef', $result['error']);
                $this->geojson = null;
                return;
            }

            if (!$result['has_data']) {
                $this->geojson = null;
                $this->addError('sigef', 'Nenhuma parcela encontrada para este município.');
                return;
            }

            $this->geojson = $result['data'];
            $this->dispatch('parcelasRecebidas', $this->geojson);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parcelas', [
                'error' => $e->getMessage(),
                'estado' => $this->data['estado'],
                'municipio' => $this->data['municipio']
            ]);

            $this->addError('sigef', 'Ocorreu um erro ao buscar as parcelas. Tente novamente mais tarde.');
            $this->geojson = null;
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'visualizador_sigef']);
    }
} 