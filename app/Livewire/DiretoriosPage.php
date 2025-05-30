<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\Servico;
use App\Services\CacheService;
use App\Services\DiretorioService;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class DiretoriosPage extends Component
{
    public $activeTab = 'clientes';
    public $search = '';
    
    public $clientes = [];
    public $projetos = [];
    public $servicos = [];
    
    public $selectedCliente = null;
    public $selectedProjeto = null;
    public $selectedServico = null;

    // Formulários
    public $form = [
        'cliente' => [
            'nome' => '',
            'email' => '',
            'telefone' => '',
            'cpf_cnpj' => '',
            'tipo_pessoa' => 'fisica',
            'observacoes' => ''
        ],
        'projeto' => [
            'nome' => '',
            'descricao' => '',
            'status' => 'ativo',
            'data_inicio' => '',
            'data_fim' => ''
        ],
        'servico' => [
            'nome' => '',
            'tipo' => 'georreferenciamento',
            'descricao' => '',
            'status' => 'pendente',
            'data_inicio' => '',
            'data_fim' => ''
        ]
    ];

    public $showForm = false;
    public $formType = null; // 'cliente', 'projeto', 'servico'
    public $editMode = false;
    public $editId = null;
    
    protected $cacheService;
    protected $diretorioService;
    
    public function boot(CacheService $cacheService, DiretorioService $diretorioService)
    {
        $this->cacheService = $cacheService;
        $this->diretorioService = $diretorioService;
    }
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        switch ($this->activeTab) {
            case 'clientes':
                $this->clientes = $this->cacheService->getClientesList($this->search);
                break;
            case 'projetos':
                if ($this->selectedCliente) {
                    $this->projetos = $this->cacheService->getProjetosList($this->selectedCliente, $this->search);
                }
                break;
            case 'servicos':
                if ($this->selectedProjeto) {
                    $this->servicos = $this->cacheService->getServicosList($this->selectedProjeto, $this->search);
                }
                break;
        }
    }
    
    public function updatedSearch()
    {
        $this->loadData();
    }
    
    public function updatedActiveTab()
    {
        $this->search = '';
        $this->showForm = false;
        $this->formType = null;
        $this->editMode = false;
        $this->editId = null;
        $this->loadData();
    }
    
    public function selectCliente($clienteId)
    {
        $this->selectedCliente = $clienteId;
        $this->selectedProjeto = null;
        $this->selectedServico = null;
        $this->activeTab = 'projetos';
        $this->loadData();
    }
    
    public function selectProjeto($projetoId)
    {
        $this->selectedProjeto = $projetoId;
        $this->selectedServico = null;
        $this->activeTab = 'servicos';
        $this->loadData();
    }
    
    public function selectServico($servicoId)
    {
        $this->selectedServico = $servicoId;
        session()->put('current_service_id', $servicoId);
        
        Notification::make()
            ->title('Sucesso')
            ->body('Diretório selecionado com sucesso!')
            ->success()
            ->send();
            
        $this->dispatch('diretorio-atualizado');
    }

    public function showCreateForm($type)
    {
        $this->formType = $type;
        $this->showForm = true;
        $this->editMode = false;
        $this->editId = null;
        $this->resetForm($type);
    }

    public function showEditForm($type, $id)
    {
        $this->formType = $type;
        $this->showForm = true;
        $this->editMode = true;
        $this->editId = $id;

        switch ($type) {
            case 'cliente':
                $model = Cliente::find($id);
                break;
            case 'projeto':
                $model = Projeto::find($id);
                break;
            case 'servico':
                $model = Servico::find($id);
                break;
        }

        if ($model) {
            $this->form[$type] = $model->toArray();
        }
    }

    public function resetForm($type)
    {
        $this->form[$type] = [
            'nome' => '',
            'email' => '',
            'telefone' => '',
            'cpf_cnpj' => '',
            'tipo_pessoa' => 'fisica',
            'observacoes' => '',
            'descricao' => '',
            'status' => $type === 'projeto' ? 'ativo' : 'pendente',
            'data_inicio' => '',
            'data_fim' => '',
            'tipo' => $type === 'servico' ? 'georreferenciamento' : ''
        ];
    }

    public function save()
    {
        $type = $this->formType;
        $data = $this->form[$type];

        try {
            if ($this->editMode) {
                switch ($type) {
                    case 'cliente':
                        $model = Cliente::find($this->editId);
                        break;
                    case 'projeto':
                        $model = Projeto::find($this->editId);
                        break;
                    case 'servico':
                        $model = Servico::find($this->editId);
                        break;
                }

                if ($model) {
                    $model->update($data);
                }
            } else {
                switch ($type) {
                    case 'cliente':
                        $model = Cliente::create($data);
                        $this->diretorioService->criarDiretorioCliente($model->nome);
                        break;
                    case 'projeto':
                        $data['cliente_id'] = $this->selectedCliente;
                        $model = Projeto::create($data);
                        $this->diretorioService->criarDiretorioProjeto(
                            $this->diretorioService->getDiretorioCliente($this->selectedCliente),
                            $model->nome
                        );
                        break;
                    case 'servico':
                        $data['projeto_id'] = $this->selectedProjeto;
                        $model = Servico::create($data);
                        $this->diretorioService->criarDiretorioServico(
                            $this->diretorioService->getDiretorioProjeto($this->selectedProjeto),
                            $model->nome
                        );
                        break;
                }
            }

            $this->showForm = false;
            $this->loadData();

            Notification::make()
                ->title('Sucesso')
                ->body($this->editMode ? 'Item atualizado com sucesso!' : 'Item criado com sucesso!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro')
                ->body('Ocorreu um erro ao salvar: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function delete($type, $id)
    {
        try {
            switch ($type) {
                case 'cliente':
                    $model = Cliente::find($id);
                    if ($model) {
                        $this->diretorioService->removerDiretorio($this->diretorioService->getDiretorioCliente($id));
                        $model->delete();
                    }
                    break;
                case 'projeto':
                    $model = Projeto::find($id);
                    if ($model) {
                        $this->diretorioService->removerDiretorio($this->diretorioService->getDiretorioProjeto($id));
                        $model->delete();
                    }
                    break;
                case 'servico':
                    $model = Servico::find($id);
                    if ($model) {
                        $this->diretorioService->removerDiretorio($this->diretorioService->getDiretorioServico($id));
                        $model->delete();
                    }
                    break;
            }

            $this->loadData();

            Notification::make()
                ->title('Sucesso')
                ->body('Item excluído com sucesso!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro')
                ->body('Ocorreu um erro ao excluir: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function render()
    {
        return view('livewire.diretorios-page');
    }
} 