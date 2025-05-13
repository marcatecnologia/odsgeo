<div>
    <div class="space-y-4">
        <!-- Abas de navegação -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'municipio')" 
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'municipio' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por Município
                </button>
                <button wire:click="$set('activeTab', 'coordenada')"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'coordenada' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Busca por Coordenada
                </button>
            </nav>
        </div>

        <!-- Formulário de busca por município -->
        <div x-show="$wire.activeTab === 'municipio'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select wire:model="estado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Selecione...</option>
                        @foreach($estados as $uf => $nome)
                            <option value="{{ $uf }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    @error('estado') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="municipio" class="block text-sm font-medium text-gray-700">Município</label>
                    <select wire:model="municipio" id="municipio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Selecione...</option>
                        @foreach($municipios as $codigo => $nome)
                            <option value="{{ $codigo }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    @error('municipio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <button wire:click="buscarParcelas" 
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span wire:loading.remove wire:target="buscarParcelas">Buscar Parcelas</span>
                    <span wire:loading wire:target="buscarParcelas">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buscando...
                    </span>
                </button>
            </div>
        </div>

        <!-- Formulário de busca por coordenada -->
        <div x-show="$wire.activeTab === 'coordenada'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="estado_coord" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select wire:model="estado" id="estado_coord" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Selecione...</option>
                        @foreach($estados as $uf => $nome)
                            <option value="{{ $uf }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    @error('estado') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="raio" class="block text-sm font-medium text-gray-700">Raio de Busca (metros)</label>
                    <input type="number" wire:model="raio" id="raio" min="100" max="10000" step="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('raio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" wire:model="latitude" id="latitude" step="any"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('latitude') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" wire:model="longitude" id="longitude" step="any"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('longitude') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button wire:click="buscarParcelasPorCoordenada" 
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span wire:loading.remove wire:target="buscarParcelasPorCoordenada">Buscar Parcelas</span>
                    <span wire:loading wire:target="buscarParcelasPorCoordenada">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buscando...
                    </span>
                </button>

                <span class="text-sm text-gray-500">
                    Ou clique no mapa para selecionar as coordenadas
                </span>
            </div>
        </div>

        @if($sigef)
            <div class="rounded-md bg-yellow-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-800">{{ $sigef }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div id="map" class="h-[600px] rounded-lg shadow-lg"></div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function () {
            let map = L.map('map').setView([-15.7801, -47.9292], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            let geojsonLayer = null;
            let centerMarker = null;
            let searchCircle = null;

            // Evento de clique no mapa
            map.on('click', function(e) {
                if ($wire.activeTab === 'coordenada') {
                    $wire.atualizarCoordenadas(e.latlng.lat, e.latlng.lng);
                    $wire.buscarParcelasPorCoordenada();
                }
            });

            Livewire.on('parcelasRecebidas', (geojson) => {
                if (geojsonLayer) {
                    map.removeLayer(geojsonLayer);
                }
                if (centerMarker) {
                    map.removeLayer(centerMarker);
                }
                if (searchCircle) {
                    map.removeLayer(searchCircle);
                }

                geojsonLayer = L.geoJSON(JSON.parse(geojson), {
                    style: {
                        color: '#FFD700',
                        weight: 2,
                        opacity: 0.8,
                        fillOpacity: 0.3
                    },
                    onEachFeature: function(feature, layer) {
                        if (feature.properties) {
                            let popupContent = '<div class="text-sm">';
                            for (let prop in feature.properties) {
                                popupContent += `<strong>${prop}:</strong> ${feature.properties[prop]}<br>`;
                            }
                            popupContent += '</div>';
                            layer.bindPopup(popupContent);
                        }
                    }
                }).addTo(map);

                // Adiciona marcador central e círculo de busca se houver coordenada central
                if ($wire.coordenadaCentral) {
                    centerMarker = L.marker([$wire.coordenadaCentral.lat, $wire.coordenadaCentral.lon], {
                        icon: L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div style="background-color: #FF0000; width: 10px; height: 10px; border-radius: 50%; border: 2px solid white;"></div>`,
                            iconSize: [10, 10],
                            iconAnchor: [5, 5]
                        })
                    }).addTo(map);

                    searchCircle = L.circle([$wire.coordenadaCentral.lat, $wire.coordenadaCentral.lon], {
                        radius: $wire.raio,
                        color: '#FF0000',
                        fillColor: '#FF0000',
                        fillOpacity: 0.1
                    }).addTo(map);
                }

                map.fitBounds(geojsonLayer.getBounds());
            });
        });
    </script>
    @endpush
</div> 