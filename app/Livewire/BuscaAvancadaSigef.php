<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class BuscaAvancadaSigef extends Component
{
    public $codigoImovel;
    public $ccir;
    public $cnpj;
    public $nomePropriedade;
    public $matriculaImovel;
    public $municipio;
    public $sigef;
    public $parcelas = [];
    public $erro = null;
    public $loading = false;
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
        'codigoImovel' => 'nullable|string|max:20',
        'ccir' => 'nullable|string|max:20',
        'cnpj' => 'nullable|string|max:18',
        'nomePropriedade' => 'nullable|string|max:255',
        'matriculaImovel' => 'nullable|string|max:20',
        'municipio' => 'nullable|string|max:255',
        'sigef' => 'nullable|string|max:255'
    ];

    protected $messages = [
        'codigoImovel.max' => 'O código do imóvel não pode ter mais de 20 caracteres',
        'ccir.max' => 'O número do CCIR não pode ter mais de 20 caracteres',
        'cnpj.max' => 'O CNPJ não pode ter mais de 18 caracteres',
        'nomePropriedade.max' => 'O nome da propriedade não pode ter mais de 255 caracteres',
        'matriculaImovel.max' => 'A matrícula do imóvel não pode ter mais de 20 caracteres',
        'municipio.max' => 'O nome do município não pode ter mais de 255 caracteres',
        'sigef.max' => 'O código SIGEF não pode ter mais de 255 caracteres'
    ];

    public function mount()
    {
        $this->reset(['parcelas', 'erro']);
    }

    public function buscar()
    {
        $this->validate();

        // Verifica se pelo menos um campo foi preenchido
        if (empty($this->codigoImovel) && 
            empty($this->ccir) && 
            empty($this->cnpj) && 
            empty($this->nomePropriedade) && 
            empty($this->matriculaImovel) && 
            empty($this->municipio) && 
            empty($this->sigef)) {
            $this->addError('busca', 'Preencha pelo menos um campo para realizar a busca.');
            return;
        }

        $this->loading = true;
        $this->reset(['parcelas', 'erro']);

        try {
            $sigefService = app(SigefWfsService::class);
            
            // Tenta buscar pelo código do imóvel
            if (!empty($this->codigoImovel)) {
                $result = $sigefService->getParcelasByCodigo($this->codigoImovel);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Tenta buscar pelo CCIR
            if (!empty($this->ccir)) {
                $result = $sigefService->getParcelasByCCIR($this->ccir);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Tenta buscar pelo CNPJ
            if (!empty($this->cnpj)) {
                $result = $sigefService->getParcelasByCNPJ($this->cnpj);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Tenta buscar pela matrícula do imóvel
            if (!empty($this->matriculaImovel)) {
                $result = $sigefService->getParcelasByCodigo(null, $this->matriculaImovel);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Tenta buscar pelo código SIGEF
            if (!empty($this->sigef)) {
                $result = $sigefService->getParcelasByCodigo($this->sigef);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Tenta buscar pelo nome da propriedade
            if (!empty($this->nomePropriedade)) {
                $result = $sigefService->getParcelasByNomePropriedade($this->nomePropriedade);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Tenta buscar pelo município
            if (!empty($this->municipio)) {
                $result = $sigefService->getParcelasPorMunicipio('BA', $this->municipio);
                if ($result['success'] && $result['has_data']) {
                    $this->parcelas = json_decode($result['data'], true)['features'] ?? [];
                    $this->dispatch('parcelasRecebidas', ['parcelas' => $this->parcelas]);
                    return;
                }
            }

            // Se não encontrou nada, exibe mensagem
            if (empty($this->parcelas)) {
                $this->erro = 'Nenhuma parcela encontrada com os critérios informados.';
            }

        } catch (\Exception $e) {
            Log::error('Erro na busca avançada SIGEF', [
                'error' => $e->getMessage(),
                'params' => [
                    'codigo_imovel' => $this->codigoImovel,
                    'ccir' => $this->ccir,
                    'cnpj' => $this->cnpj,
                    'nome_propriedade' => $this->nomePropriedade,
                    'matricula_imovel' => $this->matriculaImovel,
                    'municipio' => $this->municipio,
                    'sigef' => $this->sigef
                ]
            ]);

            $this->erro = 'Ocorreu um erro ao realizar a busca. Por favor, tente novamente mais tarde.';
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.busca-avancada-sigef');
    }
} 