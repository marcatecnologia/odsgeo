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

    <!-- Botão de Centralizar Brasil -->
    <div class="mb-2 flex justify-end">
        <button type="button" id="btnCentralizarBrasil" class="px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 transition">Centralizar Brasil</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.8.0/proj4.js"></script>
<style>
    #map {
        width: 100%;
        height: 500px;
        position: relative;
        z-index: 1;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    #map canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        background: #fffbe6 !important; /* fundo amarelo claro temporário para depuração */
    }

    .ol-layer {
        z-index: 2;
    }

    .ol-zoom {
        z-index: 3;
    }

    .ol-attribution {
        z-index: 3;
    }

    .ol-control {
        z-index: 3;
    }

    .ol-overlay-container {
        z-index: 4;
    }

    /* Garante que o mapa não seja afetado por estilos globais */
    #map * {
        box-sizing: border-box;
    }

    /* Remove qualquer transformação que possa afetar o canvas */
    #map canvas {
        transform: none !important;
        filter: none !important;
        mix-blend-mode: normal !important;
    }

    /* Garante que o mapa tenha prioridade sobre outros elementos */
    .map-container {
        position: relative;
        z-index: 1;
    }
</style>
<script>
    console.log('Script do mapa carregado!');
    proj4.defs("EPSG:4674", "+proj=longlat +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +no_defs");
    if (ol && ol.proj && ol.proj.proj4) {
        ol.proj.proj4.register(proj4);
    }

function waitForMapDiv(callback) {
    const mapDiv = document.getElementById('map');
    if (mapDiv) {
        console.log('DIV MAP encontrado:', mapDiv);
        callback();
    } else {
        console.log('DIV MAP ainda não existe, aguardando...');
        setTimeout(() => waitForMapDiv(callback), 100);
    }
}

let map, osmLayer, satelliteLayer, estadoLayer, municipioLayer, centroideLayer;

function initMap() {
    osmLayer = new ol.layer.Tile({
        source: new ol.source.OSM(),
        visible: true
    });
    satelliteLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            maxZoom: 19
        }),
        visible: false
    });
    // Camada vetorial para o perímetro do estado
    estadoLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#3388ff',
                width: 3
            }),
            fill: null
        })
    });
    // Camada vetorial fixa para o município
    municipioLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#3388ff', // azul elegante
                width: 3
            }),
            fill: new ol.style.Fill({
                color: 'rgba(51,136,255,0.08)' // azul translúcido sutil
            })
        }),
        zIndex: 99
    });

    // Corrigido: apenas atribuição, sem const/let
    centroideLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        style: new ol.style.Style({
            image: new ol.style.Circle({
                radius: 18,
                fill: new ol.style.Fill({
                    color: 'rgba(0,255,0,0.8)'
                }),
                stroke: new ol.style.Stroke({
                    color: '#000',
                    width: 4
                })
            })
        }),
        zIndex: 100
    });

    map = new ol.Map({
        target: 'map',
        layers: [osmLayer, satelliteLayer, estadoLayer, municipioLayer, centroideLayer],
        view: new ol.View({
            center: ol.proj.fromLonLat([-54.0, -15.0]),
            zoom: 4,
            projection: 'EPSG:3857',
            extent: ol.proj.transformExtent([-74, -34, -34, 5], 'EPSG:4326', 'EPSG:3857')
        })
    });
    console.log('OSM visível:', osmLayer.getVisible());
    console.log('Satélite visível:', satelliteLayer.getVisible());
    document.getElementById('toggleSatellite').addEventListener('change', function(e) {
        osmLayer.setVisible(!e.target.checked);
        satelliteLayer.setVisible(e.target.checked);
        console.log('OSM visível:', osmLayer.getVisible());
        console.log('Satélite visível:', satelliteLayer.getVisible());
    });
}

function fitBrasil() {
    console.log('Aplicando zoom no Brasil');
    try {
        const brasilExtent = ol.proj.transformExtent([-74, -34, -34, 5], 'EPSG:4326', 'EPSG:3857');
        console.log('Extent do Brasil:', brasilExtent);
        map.getView().fit(brasilExtent, {
            padding: [50, 50, 50, 50],
            duration: 1000,
            maxZoom: 5
        });
        setTimeout(() => {
            map.updateSize();
            console.log('updateSize chamado (fitBrasil)');
        }, 700);
        console.log('Zoom no Brasil aplicado com sucesso');
    } catch (error) {
        console.error('Erro ao aplicar zoom no Brasil:', error);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOMContentLoaded disparado!');
    waitForMapDiv(function() {
        initMap();
        fitBrasil();
        setTimeout(() => {
            map.updateSize();
            console.log('updateSize chamado (init)');
        }, 500);
    });
    // Botão Centralizar Brasil
    const btnCentralizar = document.getElementById('btnCentralizarBrasil');
    if (btnCentralizar) {
        btnCentralizar.addEventListener('click', function() {
            if (typeof fitBrasil === 'function') {
                fitBrasil();
            }
        });
    }
});

// Aguarda o Livewire estar disponível antes de registrar o evento
function waitForLivewire(callback) {
    if (typeof window.Livewire !== 'undefined' && typeof window.Livewire.on === 'function') {
        console.log('Livewire disponível!');
        callback();
    } else {
        console.log('Aguardando Livewire...');
        setTimeout(() => waitForLivewire(callback), 100);
    }
}

waitForLivewire(function() {
    Livewire.on('estadoSelecionado', (data) => {
        setTimeout(() => {
            waitForMapDiv(function() {
                if (map) {
                    try {
                        map.setTarget(null);
                    } catch (e) { console.warn('Erro ao destruir mapa antigo:', e); }
                    map = null;
                }
                initMap();
                setTimeout(() => {
                    if (Array.isArray(data)) {
                        data = data[0];
                    }
                    console.log('Dados recebidos do estado:', data);
                    try {
                        if (!data || !data.features || !Array.isArray(data.features) || data.features.length === 0) {
                            console.warn('Nenhuma feature encontrada');
                            fitBrasil();
                            return;
                        }
                        const featureGeoJson = data.features[0];
                        console.log('Feature GeoJSON:', featureGeoJson);
                        const geojsonFormat = new ol.format.GeoJSON({
                            dataProjection: 'EPSG:4674',
                            featureProjection: 'EPSG:3857'
                        });
                        const feature = geojsonFormat.readFeature(featureGeoJson);
                        console.log('Feature criada:', feature);
                        if (!feature || !feature.getGeometry()) {
                            console.warn('Feature ou geometria inválida');
                            fitBrasil();
                            return;
                        }
                        // Adiciona o perímetro do estado na camada vetorial
                        estadoLayer.getSource().clear();
                        estadoLayer.getSource().addFeature(feature);
                        const extent = feature.getGeometry().getExtent();
                        console.log('Extent da feature:', extent);
                        const isExtentValid = extent &&
                            extent.length === 4 &&
                            extent.every(coord => isFinite(coord)) &&
                            extent[0] > -9e6 && extent[2] < -3e6 &&
                            extent[1] > -5e6 && extent[3] < 7e6 &&
                            extent[0] < extent[2] && extent[1] < extent[3];
                        if (!isExtentValid) {
                            console.warn('Extent inválido ou fora do Brasil:', extent);
                            fitBrasil();
                            return;
                        }
                        map.getView().fit(extent, {
                            padding: [50, 50, 50, 50],
                            duration: 1000,
                            maxZoom: 8,
                            minZoom: 4
                        });
                        setTimeout(() => {
                            map.updateSize();
                            console.log('updateSize chamado (fit estado)');
                        }, 500);
                        console.log('Zoom aplicado com sucesso');
                        setTimeout(() => {
                            const mapDiv = document.getElementById('map');
                            console.log('ChildNodes do #map:', mapDiv.childNodes);
                            if (mapDiv.childNodes.length > 0) {
                                mapDiv.childNodes.forEach((el, idx) => {
                                    console.log('Elemento filho', idx, el, window.getComputedStyle(el));
                                });
                            }
                        }, 1000);
                    } catch (error) {
                        console.error('Erro ao processar estado:', error);
                        console.error('Stack trace:', error.stack);
                        fitBrasil();
                    }
                }, 100);
            });
        }, 200);
    });

    Livewire.on('municipioSelecionado', (data) => {
        console.log('Evento municipioSelecionado recebido:', data);
        // Destruir e recriar o mapa igual ao evento de estado
        setTimeout(() => {
            waitForMapDiv(function() {
                if (map) {
                    try {
                        map.setTarget(null);
                    } catch (e) { console.warn('Erro ao destruir mapa antigo:', e); }
                    map = null;
                }
                initMap();
                setTimeout(() => {
                    // Limpa camadas
                    municipioLayer.getSource().clear();
                    centroideLayer.getSource().clear();

                    // Garante que data seja FeatureCollection
                    if (Array.isArray(data)) {
                        data = data[0];
                    }

                    try {
                        if (!data || !data.features || !Array.isArray(data.features) || data.features.length === 0) {
                            console.warn('Nenhuma feature encontrada');
                            return;
                        }

                        const featureGeoJson = data.features[0];
                        console.log('Feature GeoJSON do município:', featureGeoJson);

                        const geojsonFormat = new ol.format.GeoJSON({
                            dataProjection: 'EPSG:4674',
                            featureProjection: 'EPSG:3857'
                        });

                        const feature = geojsonFormat.readFeature(featureGeoJson);
                        console.log('Feature do município criada:', feature);

                        if (!feature || !feature.getGeometry()) {
                            console.warn('Feature ou geometria inválida');
                            return;
                        }

                        const geometry = feature.getGeometry();
                        console.log('Tipo de geometria:', geometry.getType());
                        if (geometry.getType() === 'MultiPolygon' || geometry.getType() === 'Polygon') {
                            const coords = geometry.getCoordinates();
                            console.log('Quantidade de polígonos:', coords.length);
                            if (coords.length > 0) {
                                console.log('Primeiro polígono (primeiros 5 pontos):', coords[0][0].slice(0,5));
                            }
                        }

                        // Adiciona o perímetro do município
                        municipioLayer.getSource().addFeature(feature);
                        console.log('Features na camada do município:', municipioLayer.getSource().getFeatures().length);
                        console.log('Camada município visível:', municipioLayer.getVisible());

                        // Calcula e adiciona o centroide
                        const extent = geometry.getExtent();
                        const center = ol.extent.getCenter(extent);
                        console.log('Extent do município:', extent);
                        console.log('Centroide do município:', center);

                        // Validação do extent
                        const isExtentValid = extent &&
                            extent.length === 4 &&
                            extent.every(coord => isFinite(coord)) &&
                            extent[0] < extent[2] && extent[1] < extent[3];
                        if (!isExtentValid) {
                            console.warn('Extent do município inválido:', extent);
                            return;
                        }

                        // Remover centroide: não adicionar ponto verde

                        // Aplica zoom na geometria do município
                        map.getView().fit(extent, {
                            padding: [50, 50, 50, 50],
                            duration: 1000,
                            maxZoom: 15
                        });
                        setTimeout(() => {
                            map.updateSize();
                            console.log('updateSize chamado após fit município');
                        }, 700);

                        // Força updateSize extra
                        setTimeout(() => {
                            map.updateSize();
                            console.log('updateSize extra (depuração)');
                        }, 1500);

                    } catch (error) {
                        console.error('Erro ao processar município:', error);
                        console.error('Stack trace:', error.stack);
                    }
                }, 100);
            });
        }, 200);
    });
});
</script>
@endpush 