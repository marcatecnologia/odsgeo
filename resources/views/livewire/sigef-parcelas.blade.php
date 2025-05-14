@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .custom-select, .custom-input {
        background-color: #1f2937 !important;
        color: #fff !important;
        border: 1px solid #4b5563 !important;
        border-radius: 0.5rem;
    }
    .custom-select:focus, .custom-input:focus {
        border-color: #FFD700 !important;
        box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.3) !important;
    }
    .custom-select option {
        background: #1f2937;
        color: #fff;
    }
    .custom-input::placeholder {
        color: #9ca3af !important;
    }
    #map {
        min-height: 400px;
        height: 500px;
        width: 100%;
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

<div class="max-w-5xl mx-auto p-4">
    <div class="bg-gray-900 rounded-xl p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-6">Buscar Parcelas SIGEF</h2>
        <form wire:submit.prevent="buscarParcelasPorCoordenada" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1" for="estado">Estado</label>
                    <select id="estado-select" name="estado" class="custom-select w-full px-4 py-2" autocomplete="off">
                        <option value="">Selecione o estado</option>
                        @foreach($estados as $uf => $nome)
                            <option value="{{ $uf }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    @error('estado')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1" for="municipio">Município</label>
                    <select id="municipio-select" name="municipio" class="custom-select w-full px-4 py-2" autocomplete="off" disabled>
                        <option value="">Selecione o município</option>
                    </select>
                    @error('municipio')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div id="map"></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1" for="latitude">Latitude</label>
                    <input type="text" id="latitude" wire:model.defer="latitude" class="custom-input w-full px-4 py-2" placeholder="Ex: -23.550520">
                    <p class="text-xs text-gray-400 mt-1">Clique no mapa para preencher.</p>
                    @error('latitude')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1" for="longitude">Longitude</label>
                    <input type="text" id="longitude" wire:model.defer="longitude" class="custom-input w-full px-4 py-2" placeholder="Ex: -46.633308">
                    <p class="text-xs text-gray-400 mt-1">Clique no mapa para preencher.</p>
                    @error('longitude')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1" for="raio">Raio (metros)</label>
                    <input type="text" id="raio" wire:model.defer="raio" class="custom-input w-full px-4 py-2" placeholder="Ex: 1000">
                    <p class="text-xs text-gray-400 mt-1">O círculo será desenhado no mapa.</p>
                    @error('raio')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded-lg transition duration-150 ease-in-out disabled:opacity-50" wire:loading.attr="disabled" @if(!$latitude || !$longitude || !$raio || !$estado || !$municipio) disabled @endif>
                <span wire:loading.remove>Buscar Parcelas</span>
                <span wire:loading>Buscando...</span>
            </button>
        </form>
        @if(session('info'))
            <div class="mt-4 bg-blue-900 text-blue-200 px-4 py-3 rounded">
                {{ session('info') }}
            </div>
        @endif
        @if($parcelasGeojson && count($parcelasGeojson['features']) > 0)
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-white mb-3">Parcelas Encontradas</h3>
                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                    @foreach($parcelasGeojson['features'] as $parcela)
                        <div class="bg-gray-800 p-4 rounded-lg">
                            <p class="text-white text-sm">
                                <span class="font-medium">Número:</span> {{ $parcela['properties']['numero'] ?? 'N/A' }}
                            </p>
                            <p class="text-white text-sm">
                                <span class="font-medium">Área:</span> {{ $parcela['properties']['area'] ?? 'N/A' }} ha
                            </p>
                            <p class="text-white text-sm">
                                <span class="font-medium">Município:</span> {{ $parcela['properties']['municipio'] ?? 'N/A' }}
                            </p>
                            <p class="text-white text-sm">
                                <span class="font-medium">UF:</span> {{ $parcela['properties']['uf'] ?? 'N/A' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($parcelasGeojson && count($parcelasGeojson['features']) === 0)
            <div class="mt-4 bg-blue-900 text-blue-200 px-4 py-3 rounded">
                Nenhuma parcela encontrada para os parâmetros informados.
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let map, marker, circle, parcelasLayer;
    let municipioLayer = null;
    let estadoLayer = null;
    let centroides = {};

    function setMapViewToMunicipio(centroide) {
        if (map && centroide) {
            map.setView([centroide.lat, centroide.lng], 13);
        }
    }

    function drawMarkerAndCircle(lat, lng, raio) {
        if (!map) return;
        if (marker) map.removeLayer(marker);
        if (circle) map.removeLayer(circle);
        marker = L.marker([lat, lng], {icon: L.icon({iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-red.png', iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34], shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png'})}).addTo(map);
        circle = L.circle([lat, lng], {radius: Number(raio) || 1000, color: '#FFD700', fillColor: '#FFD700', fillOpacity: 0.15}).addTo(map);
    }

    function renderParcelasGeojson(geojson) {
        if (!map) return;
        if (parcelasLayer) map.removeLayer(parcelasLayer);
        if (geojson && geojson.features && geojson.features.length > 0) {
            parcelasLayer = L.geoJSON(geojson, {
                style: {color: '#FFD700', weight: 2, opacity: 0.8, fillOpacity: 0.3},
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
            map.fitBounds(parcelasLayer.getBounds());
        }
    }

    // Função para desenhar o limite do estado
    function desenharLimiteEstado(uf) {
        fetch('/geojson/limites/estados.geojson')
            .then(res => res.json())
            .then(geojson => {
                if (estadoLayer) map.removeLayer(estadoLayer);
                // Ajuste o campo conforme o seu GeoJSON, geralmente SIGLA_UF ou UF
                const feature = geojson.features.find(f => 
                    f.properties.SIGLA_UF === uf || f.properties.UF === uf
                );
                if (feature) {
                    estadoLayer = L.geoJSON(feature, {
                        style: { color: '#00BFFF', weight: 3, fillOpacity: 0 }
                    }).addTo(map);
                    map.fitBounds(estadoLayer.getBounds());
                }
            });
    }

    // Função para desenhar o limite do município
    function desenharLimiteMunicipio(codigo_ibge) {
        fetch('/geojson/limites/municipios.geojson')
            .then(res => res.json())
            .then(geojson => {
                if (municipioLayer) map.removeLayer(municipioLayer);
                // Ajuste o campo conforme o seu GeoJSON, geralmente CD_MUN, CODIGO_IBGE ou similar
                const feature = geojson.features.find(f => 
                    f.properties.CD_MUN == codigo_ibge || 
                    f.properties.CODIGO_IBGE == codigo_ibge || 
                    f.properties.cod_ibge == codigo_ibge
                );
                if (feature) {
                    municipioLayer = L.geoJSON(feature, {
                        style: { color: '#FFD700', weight: 2, fillOpacity: 0.1 }
                    }).addTo(map);
                    map.fitBounds(municipioLayer.getBounds());
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!map) {
            map = L.map('map').setView([-15.77972, -47.92972], 4);
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles © Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
            }).addTo(map);
        }
        // Clique no mapa para preencher lat/lng
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            window.livewire.find(document.querySelector('[wire\:id]').getAttribute('wire:id')).set('latitude', lat.toFixed(6));
            window.livewire.find(document.querySelector('[wire\:id]').getAttribute('wire:id')).set('longitude', lng.toFixed(6));
            drawMarkerAndCircle(lat, lng, document.getElementById('raio').value || 1000);
        });

        // Carregamento de municípios via JS puro
        const estadoSelect = document.getElementById('estado-select');
        const municipioSelect = document.getElementById('municipio-select');
        estadoSelect.addEventListener('change', function () {
            const uf = this.value;
            municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
            municipioSelect.disabled = true;
            if (!uf) {
                municipioSelect.innerHTML = '<option value="">Selecione o município</option>';
                municipioSelect.disabled = true;
                return;
            }
            // Desenhar limite do estado
            desenharLimiteEstado(uf);
            // ATENÇÃO: Os arquivos devem estar em public/municipios/UF.json
            fetch(`/municipios/${uf}.json`)
                .then(response => response.json())
                .then(data => {
                    municipioSelect.innerHTML = '<option value="">Selecione o município</option>';
                    centroides = {};
                    data.forEach(mun => {
                        municipioSelect.innerHTML += `<option value="${mun.codigo_ibge}">${mun.nome}</option>`;
                        centroides[mun.codigo_ibge] = {lat: mun.lat, lng: mun.lng};
                    });
                    municipioSelect.disabled = false;
                })
                .catch(() => {
                    municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios (verifique se o arquivo está em /public/municipios/' + uf + '.json)</option>';
                    municipioSelect.disabled = true;
                });
        });

        municipioSelect.addEventListener('change', function () {
            const codigo = this.value;
            if (centroides[codigo] && centroides[codigo].lat && centroides[codigo].lng) {
                window.livewire.emit('municipioCentralizado', centroides[codigo]);
            }
            // Atualiza o campo hidden do Livewire
            window.livewire.find(document.querySelector('[wire\:id]').getAttribute('wire:id')).set('municipio', codigo);
            window.livewire.find(document.querySelector('[wire\:id]').getAttribute('wire:id')).set('estado', estadoSelect.value);
            // Desenhar limite do município
            desenharLimiteMunicipio(codigo);
        });
    });

    // Atualiza marcador/círculo ao alterar campos
    document.addEventListener('input', function(e) {
        if (['latitude','longitude','raio'].includes(e.target.id)) {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);
            const raio = parseFloat(document.getElementById('raio').value) || 1000;
            if (!isNaN(lat) && !isNaN(lng)) {
                drawMarkerAndCircle(lat, lng, raio);
            }
        }
    });

    // Renderiza parcelas ao receber GeoJSON
    window.livewire.on('parcelasGeojsonAtualizadas', geojson => {
        renderParcelasGeojson(geojson);
    });
</script>
@endpush 