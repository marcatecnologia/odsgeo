<div class="p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="col-span-1">
            <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
            <pre class="text-xs text-gray-400 bg-gray-900 p-2 mb-2 rounded">Estados: {{ var_export($estados, true) }}</pre>
            <select wire:model="estadoSelecionado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Selecione um estado</option>
                @foreach($estados as $sigla => $nome)
                    <option value="{{ (string)$sigla }}">{{ $nome }} ({{ $sigla }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-span-1">
            <label for="municipio" class="block text-sm font-medium text-gray-700">Município</label>
            <pre class="text-xs text-gray-400 bg-gray-900 p-2 mb-2 rounded">Estado selecionado: {{ $estadoSelecionado }}</pre>
            <select wire:model="municipioSelecionado" id="municipio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ empty($municipios) ? 'disabled' : '' }}>
                <option value="">Selecione um município</option>
                @foreach($municipios as $municipio)
                    <option value="{{ $municipio->codigo_ibge }}">{{ $municipio->nome }}</option>
                @endforeach
            </select>
            @if(!empty($municipios))
                <pre class="text-xs text-gray-400 bg-gray-900 p-2 mt-2 rounded">{{ var_export($municipios, true) }}</pre>
            @endif
        </div>
    </div>

    <div class="relative h-[600px] w-full rounded-lg overflow-hidden shadow-lg">
        <div id="map" class="h-full w-full"></div>
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
<script src="https://cdn.jsdelivr.net/npm/ol@v7.4.0/ol.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v7.4.0/ol.css">

<script>
document.addEventListener('livewire:initialized', function () {
    // Configuração inicial do mapa
    const map = new ol.Map({
        target: 'map',
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            })
        ],
        view: new ol.View({
            center: ol.proj.fromLonLat([-54.0, -15.0]), // Centro do Brasil
            zoom: 4
        })
    });

    // Camada para as parcelas
    const parcelasLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
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

    map.addLayer(parcelasLayer);

    // Função para carregar parcelas do GeoServer
    function carregarParcelas(codigoMunicipio) {
        const source = parcelasLayer.getSource();
        source.clear();

        const geoserverUrl = '{{ config("geoserver.url") }}/{{ config("geoserver.workspace") }}/wfs';
        const params = {
            service: 'WFS',
            version: '2.0.0',
            request: 'GetFeature',
            typeName: '{{ config("geoserver.layer") }}',
            outputFormat: 'application/json',
            srsName: 'EPSG:3857',
            CQL_FILTER: `codigo_municipio = '${codigoMunicipio}'`
        };

        fetch(geoserverUrl + '?' + new URLSearchParams(params))
            .then(response => response.json())
            .then(data => {
                const features = new ol.format.GeoJSON().readFeatures(data, {
                    featureProjection: 'EPSG:3857'
                });
                source.addFeatures(features);
            })
            .catch(error => {
                console.error('Erro ao carregar parcelas:', error);
            });
    }

    // Função para dar zoom no estado
    function zoomToEstado(estado) {
        const geoserverUrl = '{{ config("geoserver.url") }}/{{ config("geoserver.workspace") }}/wfs';
        const params = {
            service: 'WFS',
            version: '2.0.0',
            request: 'GetFeature',
            typeName: 'municipios_simplificado',
            outputFormat: 'application/json',
            srsName: 'EPSG:3857',
            CQL_FILTER: `uf = '${estado}'`
        };

        fetch(geoserverUrl + '?' + new URLSearchParams(params))
            .then(response => response.json())
            .then(data => {
                const features = new ol.format.GeoJSON().readFeatures(data, {
                    featureProjection: 'EPSG:3857'
                });
                const extent = features.reduce((extent, feature) => {
                    return extent.extend(feature.getGeometry().getExtent());
                }, new ol.extent.createEmpty());
                
                map.getView().fit(extent, {
                    padding: [50, 50, 50, 50],
                    duration: 1000
                });
            })
            .catch(error => {
                console.error('Erro ao dar zoom no estado:', error);
            });
    }

    // Função para dar zoom no município
    function zoomToMunicipio(codigoMunicipio) {
        const geoserverUrl = '{{ config("geoserver.url") }}/{{ config("geoserver.workspace") }}/wfs';
        const params = {
            service: 'WFS',
            version: '2.0.0',
            request: 'GetFeature',
            typeName: 'municipios_simplificado',
            outputFormat: 'application/json',
            srsName: 'EPSG:3857',
            CQL_FILTER: `codigo_ibge = '${codigoMunicipio}'`
        };

        fetch(geoserverUrl + '?' + new URLSearchParams(params))
            .then(response => response.json())
            .then(data => {
                const features = new ol.format.GeoJSON().readFeatures(data, {
                    featureProjection: 'EPSG:3857'
                });
                const extent = features[0].getGeometry().getExtent();
                
                map.getView().fit(extent, {
                    padding: [50, 50, 50, 50],
                    duration: 1000
                });

                carregarParcelas(codigoMunicipio);
            })
            .catch(error => {
                console.error('Erro ao dar zoom no município:', error);
            });
    }

    // Listeners para eventos do Livewire
    Livewire.on('zoomToEstado', (data) => {
        zoomToEstado(data.estado);
    });

    Livewire.on('zoomToMunicipio', (data) => {
        zoomToMunicipio(data.municipio);
    });
});
</script>
@endpush 