<div>
    <div class="space-y-6">
        <!-- Formulário de Busca -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Busca Avançada de Parcelas SIGEF</h2>
            
            <form wire:submit.prevent="buscar" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Código do Imóvel -->
                    <div>
                        <label for="codigoImovel" class="block text-sm font-medium text-gray-700">Código do Imóvel</label>
                        <input type="text" wire:model="codigoImovel" id="codigoImovel" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite o código do imóvel">
                        @error('codigoImovel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- CCIR -->
                    <div>
                        <label for="ccir" class="block text-sm font-medium text-gray-700">CCIR</label>
                        <input type="text" wire:model="ccir" id="ccir" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite o número do CCIR">
                        @error('ccir') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- CNPJ -->
                    <div>
                        <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                        <input type="text" wire:model="cnpj" id="cnpj" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite o CNPJ">
                        @error('cnpj') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Nome da Propriedade -->
                    <div>
                        <label for="nomePropriedade" class="block text-sm font-medium text-gray-700">Nome da Propriedade</label>
                        <input type="text" wire:model="nomePropriedade" id="nomePropriedade" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite o nome da propriedade">
                        @error('nomePropriedade') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Matrícula do Imóvel -->
                    <div>
                        <label for="matriculaImovel" class="block text-sm font-medium text-gray-700">Matrícula do Imóvel</label>
                        <input type="text" wire:model="matriculaImovel" id="matriculaImovel" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite a matrícula do imóvel">
                        @error('matriculaImovel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Município -->
                    <div>
                        <label for="municipio" class="block text-sm font-medium text-gray-700">Município</label>
                        <input type="text" wire:model="municipio" id="municipio" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite o nome do município">
                        @error('municipio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Código SIGEF -->
                    <div>
                        <label for="sigef" class="block text-sm font-medium text-gray-700">Código SIGEF</label>
                        <input type="text" wire:model="sigef" id="sigef" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Digite o código SIGEF">
                        @error('sigef') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                @error('busca') <div class="text-red-500 text-sm mt-2">{{ $message }}</div> @enderror

                <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            wire:loading.attr="disabled">
                        <svg wire:loading wire:target="buscar" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buscar
                    </button>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        @if($loading)
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>
        @elseif($erro)
            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $erro }}</p>
                    </div>
                </div>
            </div>
        @elseif(count($parcelas) > 0)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Resultados da Busca</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Total de parcelas encontradas: {{ count($parcelas) }}</p>
                </div>
                <div class="border-t border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome da Propriedade</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Município</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área (ha)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($parcelas as $parcela)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $parcela['properties']['codigo_imovel'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $parcela['properties']['nome_propriedade'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $parcela['properties']['municipio'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($parcela['properties']['area_ha'] ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" 
                                                    class="text-primary-600 hover:text-primary-900"
                                                    wire:click="$emit('visualizarParcela', {{ json_encode($parcela) }})">
                                                Visualizar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('parcelasRecebidas', data => {
                // Aqui você pode adicionar lógica adicional para manipular os dados recebidos
                console.log('Parcelas recebidas:', data.parcelas);
            });
        });
    </script>
    @endpush
</div> 