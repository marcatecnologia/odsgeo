<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if($isSearching)
            <div class="flex items-center justify-center p-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
                <span class="ml-2 text-gray-600">Buscando parcelas...</span>
            </div>
        @endif

        @if($searchResults)
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Resultados da Busca
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ count($searchResults['features']) }} parcelas encontradas
                    </p>
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach($searchResults['features'] as $feature)
                        <div class="p-4 hover:bg-gray-50">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Número da Parcela</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $feature['properties']['numero_parcela'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Área (ha)</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ number_format($feature['properties']['area_ha'] ?? 0, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Situação</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $feature['properties']['situacao'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Tipo</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $feature['properties']['tipo'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!$isSearching && !$searchResults && !empty($data['estado']) && !empty($data['municipio']))
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Nenhuma parcela encontrada para os critérios selecionados.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            let map = null;
            let parcelasLayer = null;

            Livewire.on('parcelasAtualizadas', (parcelas, bbox) => {
                if (!map) {
                    map = L.map('map').setView([-15.7801, -47.9292], 4);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                }

                if (parcelasLayer) {
                    map.removeLayer(parcelasLayer);
                }

                parcelasLayer = L.geoJSON(parcelas, {
                    style: {
                        color: '#3388ff',
                        weight: 2,
                        fillOpacity: 0.1
                    },
                    onEachFeature: (feature, layer) => {
                        layer.bindPopup(`
                            <strong>Denominação:</strong> ${feature.properties.denominacao || '-'}<br>
                            <strong>Área:</strong> ${feature.properties.area_ha ? feature.properties.area_ha.toFixed(2) + ' ha' : '-'}<br>
                            <strong>Código:</strong> ${feature.properties.codigo || '-'}
                        `);
                    }
                }).addTo(map);

                if (bbox) {
                    map.fitBounds([
                        [bbox.minLat, bbox.minLng],
                        [bbox.maxLat, bbox.maxLng]
                    ]);
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page> 