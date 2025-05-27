<div class="sigef-card">
    {{-- <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Buscar Parcelas SIGEF</h2> --}}

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'municipio')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'municipio' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por Município
                </button>
                <button wire:click="$set('activeTab', 'coordenada')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'coordenada' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por Coordenada
                </button>
                <button wire:click="$set('activeTab', 'codigo')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'codigo' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por Código
                </button>
                <button wire:click="$set('activeTab', 'ccir')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'ccir' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por CCIR
                </button>
                <button wire:click="$set('activeTab', 'cnpj')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'cnpj' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por CNPJ
                </button>
                <button wire:click="$set('activeTab', 'nome')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'nome' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por Nome
                </button>
            </nav>
        </div>
    </div>

    <!-- Formulário de Busca -->
    <div class="mb-6">
        @if($activeTab === 'municipio')
            <form wire:submit.prevent="buscarParcelas" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                        <select wire:model.live="estado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione o estado</option>
                            @foreach($estados as $uf => $nome)
                                <option value="{{ $uf }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                        @error('estado') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="municipio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Município</label>
                        <select wire:model="municipio" id="municipio" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ !$estado ? 'disabled' : '' }}>
                            <option value="">Selecione o município</option>
                            @if($loading)
                                <option value="" disabled>Carregando municípios...</option>
                            @else
                                @foreach($municipios as $codigo => $nome)
                                    <option value="{{ $codigo }}">{{ $nome }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('municipio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $loading ? 'disabled' : '' }}>
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        @else
                            Buscar Parcelas
                        @endif
                    </button>
                </div>

                @if($erro)
                    <div class="sigef-error">
                        {{ \Illuminate\Support\Str::limit($erro, 350, '...') }}
                    </div>
                @endif
            </form>
        @elseif($activeTab === 'coordenada')
            <form wire:submit.prevent="buscarParcelasPorCoordenada" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                        <select wire:model="estado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione o estado</option>
                            @foreach($estados as $uf => $nome)
                                <option value="{{ $uf }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                        @error('estado') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="raio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Raio (metros)</label>
                        <input type="number" wire:model="raio" id="raio" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('raio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $loading ? 'disabled' : '' }}>
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        @else
                            Buscar Parcelas
                        @endif
                    </button>
                </div>

                @if($erro)
                    <div class="sigef-error">
                        {{ \Illuminate\Support\Str::limit($erro, 350, '...') }}
                    </div>
                @endif
            </form>
        @elseif($activeTab === 'codigo')
            <form wire:submit.prevent="buscarPorCodigo" class="space-y-4">
                <div>
                    <label for="codigoImovel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código do Imóvel</label>
                    <input type="text" wire:model="codigoImovel" id="codigoImovel" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('codigoImovel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mt-4">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $loading ? 'disabled' : '' }}>
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        @else
                            Buscar Parcelas
                        @endif
                    </button>
                </div>

                @if($erro)
                    <div class="sigef-error">
                        {{ \Illuminate\Support\Str::limit($erro, 350, '...') }}
                    </div>
                @endif
            </form>
        @elseif($activeTab === 'ccir')
            <form wire:submit.prevent="buscarPorCCIR" class="space-y-4">
                <div>
                    <label for="ccir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número do CCIR</label>
                    <input type="text" wire:model="ccir" id="ccir" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('ccir') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mt-4">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $loading ? 'disabled' : '' }}>
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        @else
                            Buscar Parcelas
                        @endif
                    </button>
                </div>

                @if($erro)
                    <div class="sigef-error">
                        {{ \Illuminate\Support\Str::limit($erro, 350, '...') }}
                    </div>
                @endif
            </form>
        @elseif($activeTab === 'cnpj')
            <form wire:submit.prevent="buscarPorCNPJ" class="space-y-4">
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ do Proprietário</label>
                    <input type="text" wire:model="cnpj" id="cnpj" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('cnpj') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mt-4">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $loading ? 'disabled' : '' }}>
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        @else
                            Buscar Parcelas
                        @endif
                    </button>
                </div>

                @if($erro)
                    <div class="sigef-error">
                        {{ \Illuminate\Support\Str::limit($erro, 350, '...') }}
                    </div>
                @endif
            </form>
        @elseif($activeTab === 'nome')
            <form wire:submit.prevent="buscarPorNome" class="space-y-4">
                <div>
                    <label for="nomePropriedade" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome da Propriedade</label>
                    <input type="text" wire:model="nomePropriedade" id="nomePropriedade" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('nomePropriedade') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mt-4">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $loading ? 'disabled' : '' }}>
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        @else
                            Buscar Parcelas
                        @endif
                    </button>
                </div>

                @if($erro)
                    <div class="sigef-error">
                        {{ \Illuminate\Support\Str::limit($erro, 350, '...') }}
                    </div>
                @endif
            </form>
        @endif
    </div>

    <!-- Mapa -->
    <div class="mb-6">
        <div id="map" class="w-full h-[500px] rounded-lg"></div>
    </div>

    <!-- Resultados -->
    <div class="mt-8">
        <div class="flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 dark:border-gray-700 sm:rounded-lg">
                        @if($loading)
                            <div class="flex justify-center items-center p-8">
                                <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        @elseif(count($parcelas) > 0)
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Código
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            CCIR
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Nome
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Área
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            CNPJ
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($parcelas as $parcela)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $parcela['codigo_imovel'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $parcela['ccir'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $parcela['nome_propriedade'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ number_format($parcela['area_total'], 2, ',', '.') }} ha
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $parcela['cnpj'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Nenhuma parcela encontrada
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(count($parcelas) > 0)
            <div class="mt-4 flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button wire:click="previousPage" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700" {{ $currentPage === 1 ? 'disabled' : '' }}>
                        Anterior
                    </button>
                    <button wire:click="nextPage" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700" {{ $currentPage === $totalPages ? 'disabled' : '' }}>
                        Próxima
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Mostrando <span class="font-medium">{{ ($currentPage - 1) * $perPage + 1 }}</span> a <span class="font-medium">{{ min($currentPage * $perPage, $totalParcelas) }}</span> de <span class="font-medium">{{ $totalParcelas }}</span> resultados
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button wire:click="previousPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" {{ $currentPage === 1 ? 'disabled' : '' }}>
                                <span class="sr-only">Anterior</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            @for($i = 1; $i <= $totalPages; $i++)
                                <button wire:click="gotoPage({{ $i }})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 {{ $i === $currentPage ? 'z-10 bg-indigo-50 dark:bg-indigo-900 border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-300' : '' }}">
                                    {{ $i }}
                                </button>
                            @endfor
                            <button wire:click="nextPage" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" {{ $currentPage === $totalPages ? 'disabled' : '' }}>
                                <span class="sr-only">Próxima</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    body {
        background: #181a1b;
        color: #f1f1f1;
        font-family: 'Inter', Arial, sans-serif;
    }
    .sigef-card {
        background: #23272a;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.4);
        padding: 2rem;
        margin: 2rem auto;
        max-width: 700px;
    }
    .sigef-card h2 {
        color: #fff;
        margin-bottom: 1.5rem;
    }
    .sigef-error {
        background: #b91c1c;
        color: #fff;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        word-break: break-all;
        font-size: 0.95rem;
        box-shadow: 0 2px 8px rgba(185,28,28,0.2);
        max-height: 120px;
        overflow-y: auto;
    }
    @media (max-width: 700px) {
        .sigef-card {
            padding: 1rem;
            max-width: 98vw;
        }
    }
    .tab-button {
        @apply whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm;
        @apply text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600;
    }
    .tab-button.active {
        @apply border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400;
    }
</style>
@endpush

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v7.4.0/ol.css">
<script>
    document.addEventListener('livewire:initialized', function () {
        let map;
        let vectorSource;
        let vectorLayer;
        let markerLayer;
        let markerSource;
        let marker;
        let circleLayer;
        let circleSource;
        let circle;

        function initMap() {
            // Inicializa o mapa
            map = new ol.Map({
                target: 'map',
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM()
                    })
                ],
                view: new ol.View({
                    center: ol.proj.fromLonLat([-54.0, -15.0]),
                    zoom: 4
                })
            });

            // Adiciona camada para as parcelas
            vectorSource = new ol.source.Vector();
            vectorLayer = new ol.layer.Vector({
                source: vectorSource,
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'rgba(0, 0, 255, 1.0)',
                        width: 2
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(0, 0, 255, 0.1)'
                    })
                })
            });
            map.addLayer(vectorLayer);

            // Adiciona camada para o marcador
            markerSource = new ol.source.Vector();
            markerLayer = new ol.layer.Vector({
                source: markerSource
            });
            map.addLayer(markerLayer);

            // Adiciona camada para o círculo
            circleSource = new ol.source.Vector();
            circleLayer = new ol.layer.Vector({
                source: circleSource,
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'rgba(255, 0, 0, 1.0)',
                        width: 2
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(255, 0, 0, 0.1)'
                    })
                })
            });
            map.addLayer(circleLayer);

            // Adiciona evento de clique no mapa
            map.on('click', function(evt) {
                if (@this.activeTab === 'coordenada') {
                    const coordinate = evt.coordinate;
                    const lonLat = ol.proj.transform(coordinate, 'EPSG:3857', 'EPSG:4326');
                    
                    @this.set('longitude', lonLat[0]);
                    @this.set('latitude', lonLat[1]);

                    // Atualiza o marcador
                    markerSource.clear();
                    marker = new ol.Feature({
                        geometry: new ol.geom.Point(coordinate)
                    });
                    marker.setStyle(new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 7,
                            fill: new ol.style.Fill({
                                color: 'red'
                            }),
                            stroke: new ol.style.Stroke({
                                color: 'white',
                                width: 2
                            })
                        })
                    }));
                    markerSource.addFeature(marker);

                    // Atualiza o círculo
                    updateCircle(coordinate);
                }
            });
        }

        function updateCircle(center) {
            circleSource.clear();
            if (@this.raio) {
                const radius = @this.raio;
                const circle = new ol.Feature({
                    geometry: new ol.geom.Circle(center, radius)
                });
                circleSource.addFeature(circle);
            }
        }

        function updateParcelas(parcelas) {
            vectorSource.clear();
            
            if (parcelas && parcelas.length > 0) {
                const features = parcelas.map(parcela => {
                    const geometry = new ol.format.GeoJSON().readGeometry(parcela.geometry, {
                        dataProjection: 'EPSG:4674',
                        featureProjection: 'EPSG:3857'
                    });
                    const feature = new ol.Feature({
                        geometry: geometry,
                        properties: parcela.properties
                    });
                    return feature;
                });

                vectorSource.addFeatures(features);

                // Ajusta o zoom para mostrar todas as parcelas
                const extent = vectorSource.getExtent();
                map.getView().fit(extent, {
                    padding: [50, 50, 50, 50],
                    maxZoom: 18
                });
            }
        }

        // Inicializa o mapa
        initMap();

        // Escuta eventos do Livewire
        Livewire.on('parcelasRecebidas', (data) => {
            updateParcelas(data.parcelas);
        });

        // Escuta mudanças no raio
        Livewire.on('raioAtualizado', (raio) => {
            if (marker) {
                updateCircle(marker.getGeometry().getCoordinates());
            }
        });
    });
</script>
@endpush 