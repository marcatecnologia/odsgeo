<div class="p-6">
    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
            <div class="bg-primary-600 h-2.5 rounded-full" style="width: {{ $selectedServico ? '100%' : ($selectedProjeto ? '66%' : ($selectedCliente ? '33%' : '0%')) }}"></div>
        </div>
    </div>

    <!-- Selected Path -->
    <div class="mb-6 p-4 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center space-x-2">
            <x-heroicon-o-folder class="w-5 h-5 text-white" />
            <span class="ml-2 text-gray-700 dark:text-gray-300">&nbsp;Diretório:&nbsp;</span>
            <span class="font-medium text-primary-600 dark:text-primary-400">
                @if($selectedCliente)
                    {{ $clientes->firstWhere('id', $selectedCliente)?->nome }}
                    @if($selectedProjeto)
                        <span class="text-gray-500 dark:text-gray-400">/</span>
                        {{ $projetos->firstWhere('id', $selectedProjeto)?->nome }}
                        @if($selectedServico)
                            <span class="text-gray-500 dark:text-gray-400">/</span>
                            {{ $servicos->firstWhere('id', $selectedServico)?->nome }}
                        @endif
                    @endif
                @else
                    Nenhum diretório selecionado
                @endif
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'clientes')" class="inline-block p-4 {{ $activeTab === 'clientes' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-500 hover:text-yellow-600 hover:border-yellow-300' }} rounded-t-lg">
                    Clientes
                </button>
            </li>
            @if($selectedCliente)
                <li class="mr-2">
                    <button wire:click="$set('activeTab', 'projetos')" class="inline-block p-4 {{ $activeTab === 'projetos' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-500 hover:text-yellow-600 hover:border-yellow-300' }} rounded-t-lg">
                        Projetos
                    </button>
                </li>
            @endif
            @if($selectedProjeto)
                <li class="mr-2">
                    <button wire:click="$set('activeTab', 'servicos')" class="inline-block p-4 {{ $activeTab === 'servicos' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-500 hover:text-yellow-600 hover:border-yellow-300' }} rounded-t-lg">
                        Serviços
                    </button>
                </li>
            @endif
        </ul>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex-1 max-w-sm">
            <div class="relative">
                <input type="text" wire:model.live="search" class="block w-full p-2 pr-12 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-yellow-500 dark:focus:border-yellow-500" placeholder="   Buscar...">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div>
            <x-filament::button
                wire:click="showCreateForm('cliente')"
                color="warning"
                icon="heroicon-o-plus"
                class="font-bold mb-4"
            >
                Novo Cliente
            </x-filament::button>

            @if($showForm)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-all duration-200">
                    <div class="relative bg-gray-900 rounded-2xl shadow-2xl border border-yellow-600 w-full max-w-lg p-8 animate-fade-in-up">
                        <button
                            type="button"
                            class="absolute top-4 right-4 text-gray-400 hover:text-yellow-400 transition"
                            wire:click="$set('showForm', false)"
                            aria-label="Fechar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <h2 class="text-2xl font-bold mb-6 text-yellow-400 text-center">Novo Cliente</h2>
                        <form wire:submit.prevent="save">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Nome</label>
                                    <input
                                        type="text"
                                        wire:model.defer="form.cliente.nome"
                                        placeholder="Digite o nome do cliente"
                                        required
                                        autofocus
                                        class="block w-full rounded-lg border border-gray-700 bg-gray-800 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    >
                                    @error('form.cliente.nome')
                                        <span class="text-danger text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                                    <input
                                        type="email"
                                        wire:model.defer="form.cliente.email"
                                        placeholder="Digite o e-mail"
                                        required
                                        class="block w-full rounded-lg border border-gray-700 bg-gray-800 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    >
                                    @error('form.cliente.email')
                                        <span class="text-danger text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Telefone</label>
                                    <input
                                        type="text"
                                        wire:model.defer="form.cliente.telefone"
                                        placeholder="Digite o telefone"
                                        class="block w-full rounded-lg border border-gray-700 bg-gray-800 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    >
                                    @error('form.cliente.telefone')
                                        <span class="text-danger text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">CPF/CNPJ</label>
                                    <input
                                        type="text"
                                        wire:model.defer="form.cliente.cpf_cnpj"
                                        placeholder="Digite o CPF ou CNPJ"
                                        required
                                        class="block w-full rounded-lg border border-gray-700 bg-gray-800 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    >
                                    @error('form.cliente.cpf_cnpj')
                                        <span class="text-danger text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Tipo de Pessoa</label>
                                    <select wire:model.defer="form.cliente.tipo_pessoa" required
                                        class="block w-full rounded-lg border border-gray-700 bg-gray-800 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                        <option value="fisica">Física</option>
                                        <option value="juridica">Jurídica</option>
                                    </select>
                                    @error('form.cliente.tipo_pessoa')
                                        <span class="text-danger text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Observações</label>
                                    <textarea
                                        wire:model.defer="form.cliente.observacoes"
                                        placeholder="Observações adicionais"
                                        rows="3"
                                        class="block w-full rounded-lg border border-gray-700 bg-gray-800 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    ></textarea>
                                    @error('form.cliente.observacoes')
                                        <span class="text-danger text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 mt-8">
                                <button type="submit" class="px-6 py-2 bg-yellow-500 text-white rounded-lg font-semibold shadow hover:bg-yellow-600 transition">Salvar</button>
                                <button type="button" class="px-6 py-2 bg-gray-700 text-white rounded-lg font-semibold shadow hover:bg-gray-800 transition" wire:click="$set('showForm', false)">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
        @if($activeTab === 'clientes')
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">Nome</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6">Telefone</th>
                        <th scope="col" class="py-3 px-6">CPF/CNPJ</th>
                        <th scope="col" class="py-3 px-6">Projetos</th>
                        <th scope="col" class="py-3 px-6">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="py-4 px-6 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                <button wire:click="selectCliente({{ $cliente->id }})" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ $cliente->nome }}
                                </button>
                            </td>
                            <td class="py-4 px-6">{{ $cliente->email }}</td>
                            <td class="py-4 px-6">{{ $cliente->telefone }}</td>
                            <td class="py-4 px-6">{{ $cliente->cpf_cnpj }}</td>
                            <td class="py-4 px-6">{{ $cliente->projetos_count }}</td>
                            <td class="py-4 px-6">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditForm('cliente', {{ $cliente->id }})" class="text-primary-700 hover:text-white border border-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:border-primary-500 dark:text-primary-500 dark:hover:text-white dark:hover:bg-primary-600 dark:focus:ring-primary-800">
                                        Editar
                                    </button>
                                    <button wire:click="delete('cliente', {{ $cliente->id }})" class="text-red-700 hover:text-white border border-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-800">
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($activeTab === 'projetos')
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">Nome</th>
                        <th scope="col" class="py-3 px-6">Descrição</th>
                        <th scope="col" class="py-3 px-6">Status</th>
                        <th scope="col" class="py-3 px-6">Data Início</th>
                        <th scope="col" class="py-3 px-6">Data Fim</th>
                        <th scope="col" class="py-3 px-6">Serviços</th>
                        <th scope="col" class="py-3 px-6">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projetos as $projeto)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="py-4 px-6 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                <button wire:click="selectProjeto({{ $projeto->id }})" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ $projeto->nome }}
                                </button>
                            </td>
                            <td class="py-4 px-6">{{ Str::limit($projeto->descricao, 50) }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($projeto->status === 'ativo') bg-green-100 text-green-800
                                    @elseif($projeto->status === 'concluido') bg-blue-100 text-blue-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($projeto->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-6">{{ $projeto->data_inicio ? date('d/m/Y', strtotime($projeto->data_inicio)) : '-' }}</td>
                            <td class="py-4 px-6">{{ $projeto->data_fim ? date('d/m/Y', strtotime($projeto->data_fim)) : '-' }}</td>
                            <td class="py-4 px-6">{{ $projeto->servicos_count }}</td>
                            <td class="py-4 px-6">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditForm('projeto', {{ $projeto->id }})" class="text-primary-700 hover:text-white border border-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:border-primary-500 dark:text-primary-500 dark:hover:text-white dark:hover:bg-primary-600 dark:focus:ring-primary-800">
                                        Editar
                                    </button>
                                    <button wire:click="delete('projeto', {{ $projeto->id }})" class="text-red-700 hover:text-white border border-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-800">
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($activeTab === 'servicos')
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">Nome</th>
                        <th scope="col" class="py-3 px-6">Tipo</th>
                        <th scope="col" class="py-3 px-6">Descrição</th>
                        <th scope="col" class="py-3 px-6">Status</th>
                        <th scope="col" class="py-3 px-6">Data Início</th>
                        <th scope="col" class="py-3 px-6">Data Fim</th>
                        <th scope="col" class="py-3 px-6">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicos as $servico)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="py-4 px-6 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                <button wire:click="selectServico({{ $servico->id }})" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ $servico->nome }}
                                </button>
                            </td>
                            <td class="py-4 px-6">{{ ucfirst($servico->tipo) }}</td>
                            <td class="py-4 px-6">{{ Str::limit($servico->descricao, 50) }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($servico->status === 'pendente') bg-yellow-100 text-yellow-800
                                    @elseif($servico->status === 'em_andamento') bg-blue-100 text-blue-800
                                    @elseif($servico->status === 'concluido') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($servico->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-6">{{ $servico->data_inicio ? date('d/m/Y', strtotime($servico->data_inicio)) : '-' }}</td>
                            <td class="py-4 px-6">{{ $servico->data_fim ? date('d/m/Y', strtotime($servico->data_fim)) : '-' }}</td>
                            <td class="py-4 px-6">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditForm('servico', {{ $servico->id }})" class="text-primary-700 hover:text-white border border-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:border-primary-500 dark:text-primary-500 dark:hover:text-white dark:hover:bg-primary-600 dark:focus:ring-primary-800">
                                        Editar
                                    </button>
                                    <button wire:click="delete('servico', {{ $servico->id }})" class="text-red-700 hover:text-white border border-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-800">
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <style>
    @keyframes fade-in-up {
        0% { opacity: 0; transform: translateY(40px);}
        100% { opacity: 1; transform: translateY(0);}
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.3s cubic-bezier(.4,0,.2,1);
    }
    </style>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('abrir-modal-novo-cliente', () => {
            window.dispatchEvent(new CustomEvent('abrir-modal-novo-cliente'));
        });
        
        Livewire.on('fechar-modal-novo-cliente', () => {
            window.dispatchEvent(new CustomEvent('fechar-modal-novo-cliente'));
        });
    });
</script>
@endpush 