<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class ParcelasSigef extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Parcelas SIGEF';
    protected static ?string $title = 'Consulta de Parcelas SIGEF';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.parcelas-sigef';

    public ?array $data = [];
    public ?array $searchResults = null;
    public bool $isSearching = false;

    protected SigefWfsService $wfsService;

    public function mount(SigefWfsService $wfsService): void
    {
        $this->wfsService = $wfsService;
        $this->form->fill();
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
                    $municipios = collect(json_decode($response['data'], true)['features'])
                        ->pluck('properties.nome', 'properties.codigo')
                        ->toArray();
                    
                    Cache::put($cacheKey, $municipios, 3600);
                }
            } catch (\Exception $e) {
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

    public function buscarParcelas(): void
    {
        if (empty($this->data['estado']) || empty($this->data['municipio'])) {
            return;
        }

        $this->isSearching = true;
        $this->searchResults = null;

        try {
            $estado = $this->data['estado'];
            $municipio = $this->data['municipio'];

            $response = $this->wfsService->getFeature('parcelas', [
                'CQL_FILTER' => "uf = '{$estado}' AND municipio = '{$municipio}'",
                'maxFeatures' => 100
            ]);

            if ($response['success']) {
                $this->searchResults = json_decode($response['data'], true);
                
                Notification::make()
                    ->title('Busca realizada com sucesso')
                    ->body('Parcelas encontradas: ' . count($this->searchResults['features']))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Erro na busca')
                    ->body('Não foi possível realizar a busca. Tente novamente em alguns minutos.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro na busca')
                ->body('Ocorreu um erro ao buscar as parcelas. Tente novamente em alguns minutos.')
                ->danger()
                ->send();
        } finally {
            $this->isSearching = false;
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'visualizador_sigef']);
    }
} 