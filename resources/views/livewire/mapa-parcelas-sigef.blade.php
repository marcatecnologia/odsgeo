<div class="p-4">
    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Dropdown de Estados -->
        <div>
            <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
            <select wire:model.live="estado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Selecione o estado</option>
                @foreach($estados as $uf => $nome)
                    <option value="{{ $uf }}">{{ $nome }}</option>
                @endforeach
            </select>
        </div>

        <!-- Dropdown de Municípios -->
        <div>
            <label for="municipio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Município</label>
            <select wire:model.live="municipio" id="municipio" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ !$estado ? 'disabled' : '' }}>
                <option value="">Selecione o município</option>
                @if($loading)
                    <option value="" disabled>Carregando municípios...</option>
                @else
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio['codigo'] }}">{{ $municipio['nome'] }}</option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>

    <!-- Mapa -->
    <div class="relative h-[600px] w-full rounded-lg overflow-hidden shadow-lg">
        <!-- Controle de Camada -->
        <div class="absolute top-4 right-4 z-10 bg-white p-3 rounded-lg shadow-lg border border-gray-200">
            <label class="flex items-center space-x-3 text-sm font-medium text-gray-700 cursor-pointer hover:text-indigo-600 transition-colors duration-200">
                <input type="checkbox" id="toggleSatellite" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 transition-colors duration-200">
                <span class="select-none">Visualização Satélite</span>
            </label>
        </div>
        
        <div id="map" style="height: 600px; width: 100%; min-width: 300px; min-height: 300px;"></div>
        @if($loading)
            <div class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            </div>
        @endif
    </div>

    @if($error)
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $error }}
        </div>
    @endif
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.3.0/ol.css">
<script src="https://cdn.jsdelivr.net/npm/ol@7.3.0/dist/ol.js"></script>
@endpush

@push('scripts')
<script>
function waitForOl(callback) {
    if (typeof ol !== 'undefined') {
        callback();
    } else {
        setTimeout(() => waitForOl(callback), 100);
    }
}

document.addEventListener('livewire:initialized', function () {
    waitForOl(function() {
        let map, estadoLayer, parcelasLayer, satelliteLayer;
        const brasilExtent = ol.proj.transformExtent([-74, -34, -34, 5], 'EPSG:4326', 'EPSG:3857');

        function initMap() {
            // Camada base OSM (padrão)
            const osmLayer = new ol.layer.Tile({
                source: new ol.source.OSM(),
                visible: true
            });

            // Camada de satélite (Esri World Imagery)
            satelliteLayer = new ol.layer.Tile({
                source: new ol.source.XYZ({
                    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    maxZoom: 19
                }),
                visible: false
            });

            // Inicialização do mapa com ambas as camadas base
            map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer],
                view: new ol.View({
                    center: ol.proj.fromLonLat([-54.0, -15.0]),
                    zoom: 4
                })
            });

            // Camadas vetoriais
            estadoLayer = new ol.layer.Vector({ 
                source: new ol.source.Vector(),
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#3388ff',
                        width: 2
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(51, 136, 255, 0.1)'
                    })
                })
            });
            
            parcelasLayer = new ol.layer.Vector({ 
                source: new ol.source.Vector(),
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ff0000',
                        width: 2
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(255, 0, 0, 0.1)'
                    })
                })
            });

            map.addLayer(estadoLayer);
            map.addLayer(parcelasLayer);

            // Controle de visibilidade da camada de satélite
            document.getElementById('toggleSatellite').addEventListener('change', function(e) {
                osmLayer.setVisible(!e.target.checked);
                satelliteLayer.setVisible(e.target.checked);
            });
        }

        function fitBrasil() {
            console.log('Aplicando zoom no Brasil');
            try {
                map.getView().fit(brasilExtent, {
                    padding: [50, 50, 50, 50],
                    duration: 1000,
                    maxZoom: 5
                });
                console.log('Zoom no Brasil aplicado com sucesso');
            } catch (error) {
                console.error('Erro ao aplicar zoom no Brasil:', error);
            }
        }

        Livewire.on('estadoSelecionado', (data) => {
            console.log('Dados recebidos do estado:', data);
            const source = estadoLayer.getSource();
            source.clear();

            try {
                // Verifica se o FeatureCollection está correto
                if (!data || !data.features || !Array.isArray(data.features) || data.features.length === 0) {
                    console.warn('Nenhuma feature encontrada');
                    fitBrasil();
                    return;
                }

                // Pega a primeira feature do FeatureCollection
                const featureGeoJson = data.features[0];

                const geojsonFormat = new ol.format.GeoJSON();
                const feature = geojsonFormat.readFeature(featureGeoJson, {
                    dataProjection: 'EPSG:4674',
                    featureProjection: 'EPSG:3857'
                });

                console.log('Feature criada:', feature);

                if (!feature || !feature.getGeometry()) {
                    console.warn('Feature ou geometria inválida');
                    fitBrasil();
                    return;
                }

                // Adiciona a feature à camada
                source.addFeature(feature);
                console.log('Feature adicionada à camada');

                // Obtém o extent em SIRGAS 2000 primeiro
                const geometry4674 = feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4674');
                const extent4674 = geometry4674.getExtent();
                // Converte o extent para EPSG:3857
                const extent = ol.proj.transformExtent(extent4674, 'EPSG:4674', 'EPSG:3857');
                console.log('Extent calculado:', extent);

                if (!extent || extent.some(coord => !isFinite(coord))) {
                    console.warn('Extent inválido:', extent);
                    fitBrasil();
                    return;
                }

                // Ajusta o zoom com valores mais conservadores
                map.getView().fit(extent, {
                    padding: [100, 100, 100, 100],
                    duration: 1000,
                    maxZoom: 8,
                    minZoom: 4
                });

                console.log('Zoom aplicado com sucesso');

            } catch (error) {
                console.error('Erro ao processar estado:', error);
                fitBrasil();
            }
        });

        Livewire.on('parcelasCarregadas', (data) => {
            const source = parcelasLayer.getSource();
            source.clear();
            if (data.parcelas && data.parcelas.length > 0) {
                const features = data.parcelas.map(parcela => {
                    const geometry = new ol.format.GeoJSON().readGeometry(parcela.geometry, {
                        dataProjection: 'EPSG:4674',
                        featureProjection: 'EPSG:3857'
                    });
                    return new ol.Feature({ geometry });
                });
                source.addFeatures(features);
                map.getView().fit(source.getExtent(), { padding: [50, 50, 50, 50], duration: 1000 });
            }
        });

        initMap();
        fitBrasil();
        setTimeout(() => {
            map.updateSize();
        }, 500);
    });
});
</script>
@endpush 