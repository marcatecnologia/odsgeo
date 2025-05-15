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
                </nav>
            </div>
        </div>

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
                    <select wire:model.live="municipio" id="municipio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Selecione um município</option>
                        @foreach($municipios as $mun)
                            <option value="{{ $mun['codigo'] }}">{{ $mun['nome'] }}</option>
                        @endforeach
                    </select>
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

        <!-- Mapa -->
        <div class="mt-6">
            <div id="map" class="w-full h-96 rounded-lg"></div>
        </div>

        <!-- Mensagens de Erro -->
        @if($erro)
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-600">{{ $erro }}</p>
            </div>
        @endif

        <!-- Resultados -->
        @if(count($parcelas) > 0)
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Parcelas Encontradas</h3>
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
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function () {
        // Inicializa o mapa
        const map = L.map('map').setView([-15.7801, -47.9292], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Adiciona marcador para o centroide
        let marker = null;

        // Escuta mudanças no centroide
        Livewire.on('centroideAtualizado', (data) => {
            console.log('Evento centroideAtualizado recebido:', data);
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
    });
</script>
@endpush 