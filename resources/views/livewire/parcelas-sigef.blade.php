<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Consulta de Parcelas SiGEF</h2>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button wire:click="$set('activeTab', 'municipio')" 
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'municipio' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Buscar por Município
                    </button>
                    <button wire:click="$set('activeTab', 'coordenada')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'coordenada' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Buscar por Coordenada
                    </button>
                    <button wire:click="$set('activeTab', 'codigo')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'codigo' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Buscar por Código
                    </button>
                </nav>
            </div>
        </div>

        <!-- Loading Overlay -->
        @if($loading)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white p-4 rounded-lg shadow-lg flex items-center space-x-3">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-700">Carregando...</span>
                </div>
            </div>
        @endif

        <!-- Formulário de Busca por Município -->
        <div x-show="$wire.activeTab === 'municipio'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select wire:model.live="estado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Selecione um estado</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>

                <div>
                    <label for="municipio" class="block text-sm font-medium text-gray-700">Município</label>
                    <div class="flex gap-2">
                        <select wire:model.live="municipio" id="municipio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione um município</option>
                            @foreach($municipios as $mun)
                                <option value="{{ $mun['codigo'] }}">{{ $mun['nome'] }}</option>
                            @endforeach
                        </select>
                        <button wire:click="recarregarMunicipios" class="mt-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button wire:click="buscarParcelas" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Buscar Parcelas
                </button>
            </div>
        </div>

        <!-- Formulário de Busca por Coordenada -->
        <div x-show="$wire.activeTab === 'coordenada'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" step="any" wire:model="latitude" id="latitude" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" step="any" wire:model="longitude" id="longitude" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="raio" class="block text-sm font-medium text-gray-700">Raio (metros)</label>
                    <input type="number" wire:model="raio" id="raio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button wire:click="buscarParcelasPorCoordenada" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Buscar Parcelas
                </button>
            </div>
        </div>

        <!-- Formulário de Busca por Código -->
        <div x-show="$wire.activeTab === 'codigo'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="codigoImovel" class="block text-sm font-medium text-gray-700">Código do Imóvel</label>
                    <input type="text" wire:model="codigoImovel" id="codigoImovel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="matriculaSigef" class="block text-sm font-medium text-gray-700">Matrícula SIGEF</label>
                    <input type="text" wire:model="matriculaSigef" id="matriculaSigef" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button wire:click="buscarPorCodigo" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Buscar Parcela
                </button>
            </div>
        </div>

        <!-- Mapa -->
        <div class="mt-6">
            <div id="map" class="w-full h-96 rounded-lg"></div>
        </div>

        <!-- Mensagens de Erro e Feedback -->
        @if($erro)
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $erro }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button wire:click="novaBusca" class="text-sm font-medium text-red-600 hover:text-red-500">
                            Nova Busca
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Resultados -->
        @if(count($parcelas) > 0)
            <div class="mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Parcelas Encontradas</h3>
                    <button wire:click="recarregarParcelas" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Recarregar
                    </button>
                </div>
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        @foreach($parcelas as $parcela)
                            <li class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium text-gray-900">
                                        Parcela {{ $parcela['properties']['numero_parcela'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Área: {{ number_format($parcela['properties']['area_ha'] ?? 0, 2) }} ha
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @elseif(!$loading && !$erro)
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">Nenhuma parcela encontrada</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button wire:click="novaBusca" class="text-sm font-medium text-yellow-600 hover:text-yellow-500">
                            Nova Busca
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:initialized', function () {
    let map = null;
    let parcelasLayer = null;

    // Inicializa o mapa
    function initMap() {
        if (map) return;

        map = L.map('map').setView([-15.7801, -47.9292], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Adiciona marcador para o centroide
        let marker = null;

        // Escuta mudanças no centroide
        Livewire.on('centroideAtualizado', (data) => {
            if (marker) {
                map.removeLayer(marker);
            }
            
            if (data.lat && data.lon) {
                marker = L.marker([data.lat, data.lon]).addTo(map);
                map.setView([data.lat, data.lon], data.zoom || 10);
            }
        });

        // Escuta cliques no mapa
        map.on('click', function(e) {
            @this.set('latitude', e.latlng.lat);
            @this.set('longitude', e.latlng.lng);
        });
    }

    // Inicializa o mapa quando o componente é montado
    initMap();
});
</script>
@endpush 