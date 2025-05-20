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
        let map, estadoLayer, parcelasLayer;
        const brasilExtent = ol.proj.transformExtent([-74, -34, -34, 5], 'EPSG:4326', 'EPSG:3857');

        function initMap() {
            map = new ol.Map({
                target: 'map',
                layers: [
                    new ol.layer.Tile({ source: new ol.source.OSM() })
                ],
                view: new ol.View({
                    center: ol.proj.fromLonLat([-54.0, -15.0]),
                    zoom: 4
                })
            });
            estadoLayer = new ol.layer.Vector({ source: new ol.source.Vector() });
            map.addLayer(estadoLayer);
            parcelasLayer = new ol.layer.Vector({ source: new ol.source.Vector() });
            map.addLayer(parcelasLayer);
        }

        function fitBrasil() {
            map.getView().fit(brasilExtent, { padding: [50, 50, 50, 50], duration: 1000 });
        }

        Livewire.on('estadoSelecionado', (data) => {
            const source = estadoLayer.getSource();
            source.clear();
            if (data.geometry && data.geometry.features && data.geometry.features.length > 0) {
                const feature = new ol.format.GeoJSON().readFeature(data.geometry.features[0], {
                    dataProjection: 'EPSG:4326',
                    featureProjection: 'EPSG:3857'
                });
                source.addFeature(feature);
                map.getView().fit(feature.getGeometry().getExtent(), { padding: [50, 50, 50, 50], duration: 1000 });
            } else {
                fitBrasil();
            }
        });

        Livewire.on('parcelasCarregadas', (data) => {
            const source = parcelasLayer.getSource();
            source.clear();
            if (data.parcelas && data.parcelas.length > 0) {
                const features = data.parcelas.map(parcela => {
                    const geometry = new ol.format.GeoJSON().readGeometry(parcela.geometry, {
                        dataProjection: 'EPSG:4326',
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