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
        <!-- Controles flutuantes no canto superior direito do mapa -->
        <div style="position: absolute; top: 1rem; right: 1rem; z-index: 10; display: flex; flex-direction: row; gap: 0.75rem;">
            <div class="bg-white p-2 rounded-lg shadow border border-gray-200 flex items-center">
                <label class="flex items-center space-x-2 text-sm font-medium text-gray-700 cursor-pointer hover:text-indigo-600 transition-colors duration-200 m-0">
                    <input type="checkbox" id="toggleSatellite" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 transition-colors duration-200">
                    <span class="select-none">Visualização Satélite</span>
                </label>
            </div>
            <button type="button" id="btnCentralizarBrasil" class="bg-white p-2 rounded-lg shadow border border-gray-200 text-gray-700 hover:bg-indigo-600 hover:text-white font-medium transition flex items-center">Centralizar Brasil</button>
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

    window.GEOSERVER_URL = "{{ env('GEOSERVER_URL', 'http://host.docker.internal:8082/geoserver') }}";
    window.GEOSERVER_WORKSPACE = "{{ env('GEOSERVER_WORKSPACE', 'odsgeo') }}";
    window.GEOSERVER_LAYER = "{{ env('GEOSERVER_LAYER', 'parcelas_sigef_brasil') }}";

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

let map, osmLayer, satelliteLayer, estadoLayer, municipioLayer, centroideLayer, parcelasLayer;

function getParcelasWfsUrl(codigoMun) {
    const geoserverUrl = window.GEOSERVER_URL;
    const workspace = window.GEOSERVER_WORKSPACE;
    const layer = window.GEOSERVER_LAYER;
    return `${geoserverUrl}/wfs?service=WFS&version=1.1.0&request=GetFeature&typename=${workspace}:${layer}&outputFormat=application/json&srsname=EPSG:4674&CQL_FILTER=municipio_=${codigoMun}&propertyName=parcela_co,geom`;
}

function getParcelaDetailUrl(codCcir) {
    const geoserverUrl = window.GEOSERVER_URL;
    const workspace = window.GEOSERVER_WORKSPACE;
    const layer = window.GEOSERVER_LAYER;
    return `${geoserverUrl}/wfs?service=WFS&version=1.1.0&request=GetFeature&typename=${workspace}:${layer}&outputFormat=application/json&srsname=EPSG:4674&CQL_FILTER=cod_ccir='${codCcir}'`;
}

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

    // Camada WFS para parcelas SIGEF
    parcelasLayer = new ol.layer.Vector({
        source: new ol.source.Vector({
            format: new ol.format.GeoJSON({
                dataProjection: 'EPSG:4674',
                featureProjection: 'EPSG:3857'
            }),
            url: function(extent) {
                if (!window.currentMunicipioCodigo) {
                    console.warn('Código do município não definido para WFS!');
                    return undefined;
                }
                const url = getParcelasWfsUrl(window.currentMunicipioCodigo);
                console.log('URL WFS das parcelas SIGEF:', url);
                return url;
            },
            strategy: ol.loadingstrategy.bbox
        }),
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#555',
                width: 1.5
            }),
            fill: null
        })
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
        layers: [osmLayer, satelliteLayer, estadoLayer, municipioLayer, centroideLayer, parcelasLayer],
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

    // Adiciona interatividade para as parcelas
    const overlay = new ol.Overlay({
        element: document.createElement('div'),
        positioning: 'bottom-center',
        offset: [0, -10],
        stopEvent: false
    });
    map.addOverlay(overlay);

    // Tooltip para mostrar o código CCIR
    const tooltipElement = document.createElement('div');
    tooltipElement.className = 'tooltip';
    tooltipElement.style.position = 'absolute';
    tooltipElement.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    tooltipElement.style.color = 'white';
    tooltipElement.style.padding = '4px 8px';
    tooltipElement.style.borderRadius = '4px';
    tooltipElement.style.fontSize = '12px';
    tooltipElement.style.pointerEvents = 'none';
    tooltipElement.style.display = 'none';
    document.body.appendChild(tooltipElement);

    // Evento de hover para mostrar tooltip
    map.on('pointermove', function(evt) {
        const feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) {
            return feature;
        });

        if (feature && feature.get('cod_ccir')) {
            tooltipElement.style.display = 'block';
            tooltipElement.style.left = evt.pixel[0] + 'px';
            tooltipElement.style.top = evt.pixel[1] - 10 + 'px';
            tooltipElement.innerHTML = feature.get('cod_ccir');
        } else {
            tooltipElement.style.display = 'none';
        }
    });

    // Evento de clique para mostrar detalhes da parcela
    map.on('click', function(evt) {
        const feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) {
            return feature;
        });

        if (feature && feature.get('cod_ccir')) {
            const codCcir = feature.get('cod_ccir');
            fetchParcelaDetails(codCcir);
        }
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
                    parcelasLayer.getSource().clear();

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

                        // Armazena o código do município para uso na camada WFS
                        window.currentMunicipioCodigo = featureGeoJson.properties.cd_mun;
                        console.log('Código IBGE do município selecionado:', window.currentMunicipioCodigo);

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

                        // Atualiza a fonte da camada de parcelas (após definir o código!)
                        parcelasLayer.getSource().refresh();

                    } catch (error) {
                        console.error('Erro ao processar município:', error);
                        console.error('Stack trace:', error.stack);
                    }
                }, 100);
            });
        }, 200);
    });
});

// Função para buscar detalhes da parcela
async function fetchParcelaDetails(codCcir) {
    try {
        console.time('fetchParcelaDetails');
        const response = await fetch(getParcelaDetailUrl(codCcir));
        const data = await response.json();
        console.timeEnd('fetchParcelaDetails');
        if (data.features && data.features.length > 0) {
            showParcelaDetails(data.features[0].properties);
        }
    } catch (error) {
        console.error('Erro ao buscar detalhes da parcela:', error);
    }
}

// Função para exibir detalhes da parcela
function showParcelaDetails(properties) {
    // Criar modal com os detalhes
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Dados da Parcela</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-2">
                ${Object.entries(properties).map(([key, value]) => `
                    <div class="grid grid-cols-3 gap-4">
                        <div class="font-medium text-gray-700">${key}:</div>
                        <div class="col-span-2">${value}</div>
                    </div>
                `).join('')}
            </div>
            <div class="mt-6 flex justify-end space-x-4">
                <button onclick="salvarParcela('${properties.cod_ccir}')" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Salvar Parcela
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Função para salvar parcela
async function salvarParcela(codCcir) {
    try {
        const response = await fetch('/api/parcelas/salvar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ cod_ccir: codCcir })
        });

        if (response.ok) {
            alert('Parcela salva com sucesso!');
        } else {
            throw new Error('Erro ao salvar parcela');
        }
    } catch (error) {
        console.error('Erro ao salvar parcela:', error);
        alert('Erro ao salvar parcela. Tente novamente.');
    }
}
</script>
@endpush 