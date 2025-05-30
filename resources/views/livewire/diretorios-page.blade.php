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
                <button wire:click="$set('activeTab', 'clientes')" class="inline-block p-4 {{ $activeTab === 'clientes' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-600 hover:border-gray-300' }} rounded-t-lg">
                    Clientes
                </button>
            </li>
            @if($selectedCliente)
                <li class="mr-2">
                    <button wire:click="$set('activeTab', 'projetos')" class="inline-block p-4 {{ $activeTab === 'projetos' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-600 hover:border-gray-300' }} rounded-t-lg">
                        Projetos
                    </button>
                </li>
            @endif
            @if($selectedProjeto)
                <li class="mr-2">
                    <button wire:click="$set('activeTab', 'servicos')" class="inline-block p-4 {{ $activeTab === 'servicos' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-600 hover:border-gray-300' }} rounded-t-lg">
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
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="search" wire:model.live="search" class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Buscar...">
            </div>
        </div>
        <div>
            <button wire:click="showCreateForm('{{ $activeTab }}')" class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800">
                Novo {{ ucfirst($activeTab) }}
            </button>
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

    <!-- Form Modal -->
    @if($showForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">
                                    {{ $editMode ? 'Editar' : 'Novo' }} {{ ucfirst($formType) }}
                                </h3>
                                <div class="mt-4">
                                    <form wire:submit.prevent="save">
                                        @if($formType === 'cliente')
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Nome</label>
                                                <input type="text" wire:model="form.cliente.nome" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                                <input type="email" wire:model="form.cliente.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Telefone</label>
                                                <input type="text" wire:model="form.cliente.telefone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">CPF/CNPJ</label>
                                                <input type="text" wire:model="form.cliente.cpf_cnpj" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Tipo de Pessoa</label>
                                                <select wire:model="form.cliente.tipo_pessoa" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                    <option value="fisica">Física</option>
                                                    <option value="juridica">Jurídica</option>
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Observações</label>
                                                <textarea wire:model="form.cliente.observacoes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                            </div>
                                        @elseif($formType === 'projeto')
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Nome</label>
                                                <input type="text" wire:model="form.projeto.nome" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                                                <textarea wire:model="form.projeto.descricao" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                                <select wire:model="form.projeto.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                    <option value="ativo">Ativo</option>
                                                    <option value="concluido">Concluído</option>
                                                    <option value="cancelado">Cancelado</option>
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Data de Início</label>
                                                <input type="date" wire:model="form.projeto.data_inicio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Data de Término</label>
                                                <input type="date" wire:model="form.projeto.data_fim" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                        @elseif($formType === 'servico')
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Nome</label>
                                                <input type="text" wire:model="form.servico.nome" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                                <select wire:model="form.servico.tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                    <option value="georreferenciamento">Georreferenciamento</option>
                                                    <option value="topografia">Topografia</option>
                                                    <option value="aerofotogrametria">Aerofotogrametria</option>
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                                                <textarea wire:model="form.servico.descricao" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                                <select wire:model="form.servico.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                    <option value="pendente">Pendente</option>
                                                    <option value="em_andamento">Em Andamento</option>
                                                    <option value="concluido">Concluído</option>
                                                    <option value="cancelado">Cancelado</option>
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Data de Início</label>
                                                <input type="date" wire:model="form.servico.data_inicio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Data de Término</label>
                                                <input type="date" wire:model="form.servico.data_fim" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            </div>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button wire:click="save" type="button" class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 sm:ml-3 sm:w-auto">
                            Salvar
                        </button>
                        <button wire:click="$set('showForm', false)" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div> 