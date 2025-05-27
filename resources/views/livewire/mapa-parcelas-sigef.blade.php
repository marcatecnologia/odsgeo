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
                    <span class="select-none">Informações</span>
                </label>
            </div>
            <button type="button" id="btnCentralizarBrasil" class="bg-white p-2 rounded-lg shadow border border-gray-200 text-sm font-medium text-gray-700 hover:bg-indigo-600 hover:text-white transition flex items-center">Centralizar</button>
        </div>
        
        <div id="map" style="height: 600px; width: 100%; min-width: 300px; min-height: 300px;"></div>
        <!-- Loader centralizado sobre o mapa -->
        <div id="map-loader" style="display:none; position:absolute; left:0; top:0; width:100%; height:100%; z-index:50; background:rgba(24,24,28,0.45); backdrop-filter:blur(1.5px); align-items:center; justify-content:center;">
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">
                <div class="globe-spin" style="width:64px; height:64px; margin-bottom:18px; box-shadow:0 4px 24px 0 rgba(0,0,0,0.18); border-radius:50%; background:linear-gradient(135deg,#1e293b 60%,#38bdf8 100%); display:flex; align-items:center; justify-content:center;">
                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="22" cy="22" r="20" stroke="#fff" stroke-width="3" fill="#38bdf8"/>
                        <ellipse cx="22" cy="22" rx="14" ry="20" stroke="#fff" stroke-width="2" fill="none"/>
                        <ellipse cx="22" cy="22" rx="20" ry="8" stroke="#fff" stroke-width="2" fill="none"/>
                    </svg>
                </div>
                <div id="map-loader-text" style="color:#fff; font-size:1.1rem; font-weight:500; text-shadow:0 2px 8px #000a; letter-spacing:0.01em; margin-top:0.5rem;">Carregando...</div>
            </div>
        </div>
    </div>

    @if($error)
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $error }}
        </div>
    @endif
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.3.0/ol.css">
<script src="/vendor/ol/ol.js"></script>
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

    .globe-spin {
        animation: globe-rotate 1.2s linear infinite;
    }
    @keyframes globe-rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    #map-loader {
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: all;
        z-index: 9999 !important;
    }
    @media (max-width: 600px) {
        #map-loader .globe-spin { width: 44px; height: 44px; }
        #map-loader-text { font-size: 0.95rem; }
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

let map, osmLayer, satelliteLayer, estadoLayer, municipioLayer, centroideLayer, parcelasLayer, parcelasLabelLayer;

function getParcelasWfsUrl(codigoMun) {
    const geoserverUrl = window.GEOSERVER_URL;
    const workspace = window.GEOSERVER_WORKSPACE;
    const layer = window.GEOSERVER_LAYER;
    return `${geoserverUrl}/wfs?service=WFS&version=1.1.0&request=GetFeature&typename=${workspace}:${layer}&outputFormat=application/json&srsname=EPSG:3857&CQL_FILTER=municipio_=${codigoMun}&propertyName=parcela_co,nome_area,geom`;
}

function getParcelaDetailUrl(codCcir) {
    const geoserverUrl = window.GEOSERVER_URL;
    const workspace = window.GEOSERVER_WORKSPACE;
    const layer = window.GEOSERVER_LAYER;
    return `${geoserverUrl}/wfs?service=WFS&version=1.1.0&request=GetFeature&typename=${workspace}:${layer}&outputFormat=application/json&srsname=EPSG:3857&CQL_FILTER=cod_ccir='${codCcir}'`;
}

function initMap() {
    // Garantir que a projeção está registrada
    if (!ol.proj.get('EPSG:4674')) {
        proj4.defs("EPSG:4674", "+proj=longlat +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +no_defs");
        ol.proj.proj4.register(proj4);
    }

    // Camada OSM com tratamento de erro
    osmLayer = new ol.layer.Tile({
        source: new ol.source.OSM({
            crossOrigin: 'anonymous'
        }),
        visible: false
    });

    // Camada de satélite com tratamento de erro
    satelliteLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            maxZoom: 19,
            crossOrigin: 'anonymous'
        }),
        visible: true
    });

    // Adicionar tratamento de erro para as camadas base
    osmLayer.getSource().on('tileloaderror', function() {
        console.error('Erro ao carregar tile OSM');
    });

    satelliteLayer.getSource().on('tileloaderror', function() {
        console.error('Erro ao carregar tile de satélite');
    });

    // Camada WFS para parcelas SIGEF
    parcelasLayer = new ol.layer.Vector({
        source: new ol.source.Vector({
            format: new ol.format.GeoJSON({
                dataProjection: 'EPSG:3857',
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
                color: '#FFD600',
                width: 2.5
            }),
            fill: new ol.style.Fill({
                color: 'rgba(255,255,255,0.01)'
            })
        })
    });

    // Camada para os labels das parcelas (sempre zIndex alto)
    parcelasLabelLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        zIndex: 9999,
        style: new ol.style.Style({
            image: new ol.style.Icon({
                src: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36"><circle cx="18" cy="18" r="16" fill="white" stroke="%23e11d48" stroke-width="3"/><circle cx="18" cy="18" r="10" fill="none" stroke="%23e11d48" stroke-width="2"/><circle cx="18" cy="18" r="4" fill="%23e11d48"/></svg>',
                anchor: [0.5, 0.5],
                anchorXUnits: 'fraction',
                anchorYUnits: 'fraction',
                scale: 0.55
            })
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
        layers: [satelliteLayer, osmLayer, estadoLayer, municipioLayer, centroideLayer, parcelasLayer],
        view: new ol.View({
            center: ol.proj.fromLonLat([-54.0, -15.0]),
            zoom: 3, // Aumentado para melhor visualização inicial
            projection: 'EPSG:3857',
            extent: ol.proj.transformExtent([-85, -40, -25, 10], 'EPSG:4326', 'EPSG:3857')
        })
    });
    // Adiciona a camada de labels por último SEMPRE
    map.addLayer(parcelasLabelLayer);
    console.log('OSM visível:', osmLayer.getVisible());
    console.log('Satélite visível:', satelliteLayer.getVisible());
    document.getElementById('toggleSatellite').addEventListener('change', function(e) {
        osmLayer.setVisible(e.target.checked);
        // Força atualização do mapa
        map.render();
        console.log('OSM visível:', osmLayer.getVisible());
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
        let clickedFeature = null;
        
        // Primeiro verifica na camada de labels
        map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
            if (layer === parcelasLabelLayer) {
                clickedFeature = feature;
                return true;
            }
        });

        // Se não encontrou na camada de labels, verifica na camada de parcelas
        if (!clickedFeature) {
            map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
                if (layer === parcelasLayer) {
                    clickedFeature = feature;
                    return true;
                }
            });
        }

        // Se encontrou uma feature, mostra os detalhes
        if (clickedFeature) {
            const properties = {
                parcela_co: clickedFeature.get('parcela_co'),
                rt: clickedFeature.get('rt'),
                art: clickedFeature.get('art'),
                situacao_i: clickedFeature.get('situacao_i'),
                codigo_imo: clickedFeature.get('codigo_imo'),
                data_submi: clickedFeature.get('data_submi'),
                data_aprov: clickedFeature.get('data_aprov'),
                status: clickedFeature.get('status'),
                nome_area: clickedFeature.get('nome_area'),
                registro_m: clickedFeature.get('registro_m'),
                registro_d: clickedFeature.get('registro_d'),
                municipio_: clickedFeature.get('municipio_'),
                uf_id: clickedFeature.get('uf_id')
            };
            
            showParcelaDetails(properties);
        }
    });

    addParcelasLoaderHooks();

    // Função para adicionar labels após o carregamento das features
    function addParcelasLabels() {
        parcelasLabelLayer.getSource().clear();
        const features = parcelasLayer.getSource().getFeatures();
        let count = 0;

        features.forEach(feature => {
            let geometry = feature.getGeometry();
            const nomeArea = feature.get('nome_area');

            if (
                nomeArea &&
                geometry &&
                (geometry.getType() === 'Polygon' || geometry.getType() === 'MultiPolygon')
            ) {
                let labelPoint = null;
                if (geometry.getType() === 'Polygon' && typeof geometry.getInteriorPoint === 'function') {
                    labelPoint = geometry.getInteriorPoint();
                } else if (geometry.getType() === 'MultiPolygon' && typeof geometry.getInteriorPoints === 'function') {
                    const interiorPoints = geometry.getInteriorPoints();
                    labelPoint = interiorPoints.getPoint(0);
                }

                if (labelPoint) {
                    const markerFeature = new ol.Feature({
                        geometry: labelPoint,
                        parcela_co: feature.get('parcela_co'),
                        rt: feature.get('rt'),
                        art: feature.get('art'),
                        situacao_i: feature.get('situacao_i'),
                        codigo_imo: feature.get('codigo_imo'),
                        data_submi: feature.get('data_submi'),
                        data_aprov: feature.get('data_aprov'),
                        status: feature.get('status'),
                        nome_area: feature.get('nome_area'),
                        registro_m: feature.get('registro_m'),
                        registro_d: feature.get('registro_d'),
                        municipio_: feature.get('municipio_'),
                        uf_id: feature.get('uf_id')
                    });

                    parcelasLabelLayer.getSource().addFeature(markerFeature);
                    count++;
                }
            }
        });
        console.log('Labels de parcelas criados:', count);
    }

    // Chame essa função sempre que as features das parcelas forem carregadas/refrescadas
    parcelasLayer.getSource().on('change', function(e) {
        if (parcelasLayer.getSource().getState() === 'ready') {
            addParcelasLabels();
        }
    });

    // Forçar atualização do tamanho do mapa após inicialização
    setTimeout(() => {
        map.updateSize();
    }, 100);
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

        // Adicionar evento de redimensionamento
        window.addEventListener('resize', function() {
            if (map) {
                map.updateSize();
            }
        });
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
        showMapLoader('Buscando estado...');
        setTimeout(() => {
            waitForMapDiv(function() {
                if (map) {
                    try { map.setTarget(null); } catch (e) { console.warn('Erro ao destruir mapa antigo:', e); }
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
                        hideMapLoader();
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
        showMapLoader('Buscando município...');
        console.log('Evento municipioSelecionado recebido:', data);
        setTimeout(() => {
            waitForMapDiv(function() {
                if (map) {
                    try { map.setTarget(null); } catch (e) { console.warn('Erro ao destruir mapa antigo:', e); }
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
                    hideMapLoader();
                }, 100);
            });
        }, 200);
    });
});

// Função para buscar detalhes da parcela
async function fetchParcelaDetails(parcelaCo) {
    try {
        // Busca a feature na camada de parcelas
        const features = parcelasLayer.getSource().getFeatures();
        const feature = features.find(f => f.get('parcela_co') === parcelaCo);
        
        if (feature) {
            // Cria um objeto com as propriedades da parcela
            const properties = {
                parcela_co: feature.get('parcela_co'),
                rt: feature.get('rt'),
                art: feature.get('art'),
                situacao_i: feature.get('situacao_i'),
                codigo_imo: feature.get('codigo_imo'),
                data_submi: feature.get('data_submi'),
                data_aprov: feature.get('data_aprov'),
                status: feature.get('status'),
                nome_area: feature.get('nome_area'),
                registro_m: feature.get('registro_m'),
                registro_d: feature.get('registro_d'),
                municipio_: feature.get('municipio_'),
                uf_id: feature.get('uf_id')
            };
            
            showParcelaDetails(properties);
        } else {
            throw new Error('Parcela não encontrada');
        }
    } catch (error) {
        console.error('Erro ao buscar detalhes da parcela:', error);
    }
}

// Função para exibir detalhes da parcela
function showParcelaDetails(properties) {
    // Mapeamento de rótulos amigáveis e ícones
    const labels = {
        parcela_co: { label: 'Código Parcela', icon: '📋' },
        rt: { label: 'RT', icon: '📄' },
        art: { label: 'ART', icon: '📑' },
        situacao_i: { label: 'Situação', icon: '📊' },
        codigo_imo: { label: 'Código Imóvel', icon: '🏠' },
        data_submi: { label: 'Data Submissão', icon: '📅' },
        data_aprov: { label: 'Data Aprovação', icon: '✅' },
        status: { label: 'Status', icon: '🔄' },
        nome_area: { label: 'Nome Área', icon: '📍' },
        registro_m: { label: 'Registro M', icon: '📝' },
        registro_d: { label: 'Registro D', icon: '📝' },
        municipio_: { label: 'Município', icon: '🏙️' },
        uf_id: { label: 'UF', icon: '🏳️' }
    };

    // Função para formatar datas
    const formatarData = (data) => {
        if (!data) return '-';
        try {
            return new Date(data).toLocaleDateString('pt-BR');
        } catch {
            return data;
        }
    };

    // Função para formatar status
    const formatarStatus = (status) => {
        if (!status) return '-';
        const statusMap = {
            'A': 'Aprovado',
            'P': 'Pendente',
            'R': 'Rejeitado'
        };
        return statusMap[status] || status;
    };

    // Função para formatar situação
    const formatarSituacao = (situacao) => {
        if (!situacao) return '-';
        const situacaoMap = {
            'A': 'Ativo',
            'I': 'Inativo',
            'S': 'Suspenso'
        };
        return situacaoMap[situacao] || situacao;
    };

    // Monta os campos que possuem valor
    const campos = Object.entries(labels)
        .map(([key, info]) => {
            let valor = properties[key] || '-';
            
            // Formatação especial para alguns campos
            if (key.includes('data_')) {
                valor = formatarData(valor);
            } else if (key === 'status') {
                valor = formatarStatus(valor);
            } else if (key === 'situacao_i') {
                valor = formatarSituacao(valor);
            }

            return `<div class="odsgeo-modal-row">
                        <div class="odsgeo-modal-label">
                            <span class="odsgeo-modal-icon">${info.icon}</span>
                            <span>${info.label}</span>
                        </div>
                        <span class="odsgeo-modal-value ${valor === '-' ? 'odsgeo-modal-value-empty' : ''}">${valor}</span>
                    </div>`;
        }).join('');

    // Remove modal anterior se existir
    document.querySelectorAll('.odsgeo-modal-bg').forEach(e => e.remove());

    // Cria o modal
    const modal = document.createElement('div');
    modal.className = 'odsgeo-modal-bg';
    modal.innerHTML = `
        <div class="odsgeo-modal-content">
            <button class="odsgeo-modal-close" title="Fechar" onclick="this.closest('.odsgeo-modal-bg').remove()">&times;</button>
            <div class="odsgeo-modal-header">
                <h2 class="odsgeo-modal-title">Dados da Parcela</h2>
                <div class="odsgeo-modal-subtitle">${properties.nome_area || 'Parcela SIGEF'}</div>
            </div>
            <div class="odsgeo-modal-fields">${campos}</div>
            <div class="odsgeo-modal-actions">
                <button onclick="salvarParcela('${properties.parcela_co || ''}')" class="odsgeo-modal-btn">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Salvar Parcela
                </button>
            </div>
        </div>
        <style>
        .odsgeo-modal-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.45); z-index: 10000;
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem;
            backdrop-filter: blur(4px);
        }
        .odsgeo-modal-content {
            background: #fff; border-radius: 16px; box-shadow: 0 8px 32px #0005;
            max-width: 480px; width: 100%; padding: 2rem 1.5rem 1.5rem 1.5rem;
            position: relative; display: flex; flex-direction: column;
            animation: odsgeo-modal-in 0.2s cubic-bezier(.4,1.4,.6,1) 1;
        }
        @keyframes odsgeo-modal-in {
            from { opacity: 0; transform: translateY(40px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }
        .odsgeo-modal-close {
            position: absolute; top: 14px; right: 16px; background: none; border: none;
            font-size: 2rem; color: #888; cursor: pointer; line-height: 1;
            transition: all 0.2s; width: 32px; height: 32px; display: flex;
            align-items: center; justify-content: center; border-radius: 50%;
        }
        .odsgeo-modal-close:hover { 
            color: #e11d48; 
            background: rgba(225,29,72,0.1);
        }
        .odsgeo-modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .odsgeo-modal-title {
            font-size: 1.4rem; font-weight: 700; color: #e11d48; margin-bottom: 0.3rem;
        }
        .odsgeo-modal-subtitle {
            color: #64748b; font-size: 0.95rem;
        }
        .odsgeo-modal-fields {
            display: flex; flex-direction: column; gap: 0.8rem;
            max-height: 50vh; overflow-y: auto; margin-bottom: 1.5rem;
            padding-right: 0.5rem;
        }
        .odsgeo-modal-fields::-webkit-scrollbar {
            width: 6px;
        }
        .odsgeo-modal-fields::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        .odsgeo-modal-fields::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .odsgeo-modal-row {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;
        }
        .odsgeo-modal-label {
            display: flex; align-items: center; gap: 0.5rem;
            font-weight: 600; color: #334155; font-size: 1rem;
        }
        .odsgeo-modal-icon {
            font-size: 1.1rem;
        }
        .odsgeo-modal-value {
            color: #1e293b; font-size: 1rem; word-break: break-all;
            text-align: right; font-weight: 500;
        }
        .odsgeo-modal-value-empty {
            color: #94a3b8; font-style: italic;
        }
        .odsgeo-modal-actions {
            display: flex; justify-content: flex-end; gap: 0.5rem;
        }
        .odsgeo-modal-btn {
            background: #6366f1; color: #fff; border: none; border-radius: 8px;
            padding: 0.7rem 1.4rem; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center;
            justify-content: center;
        }
        .odsgeo-modal-btn:hover { 
            background: #4338ca;
            transform: translateY(-1px);
        }
        @media (max-width: 600px) {
            .odsgeo-modal-content { 
                max-width: 98vw; 
                padding: 1.5rem 1rem 1rem 1rem;
                border-radius: 12px;
            }
            .odsgeo-modal-title { font-size: 1.2rem; }
            .odsgeo-modal-label, .odsgeo-modal-value { font-size: 0.95rem; }
            .odsgeo-modal-btn { padding: 0.6rem 1.2rem; }
        }
        </style>
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

function showMapLoader(text) {
    const loader = document.getElementById('map-loader');
    const loaderText = document.getElementById('map-loader-text');
    if (loader && loaderText) {
        loaderText.textContent = text || 'Carregando...';
        loader.style.display = 'flex';
    }
}
function hideMapLoader() {
    const loader = document.getElementById('map-loader');
    if (loader) loader.style.display = 'none';
}

document.addEventListener('livewire:load', function () {
    if (window.livewire) {
        window.livewire.hook('message.sent', () => {
            showMapLoader('Carregando...');
        });
        window.livewire.hook('message.processed', () => {
            hideMapLoader();
        });
    }
});

// Loader para as parcelas SIGEF (camada WFS)
function addParcelasLoaderHooks() {
    if (typeof parcelasLayer !== 'undefined' && parcelasLayer.getSource()) {
        parcelasLayer.getSource().on('featuresloadstart', function() {
            showMapLoader('Buscando parcelas...');
        });
        parcelasLayer.getSource().on('featuresloadend', function() {
            hideMapLoader();
        });
        parcelasLayer.getSource().on('featuresloaderror', function() {
            hideMapLoader();
        });
    }
}

// Adicionar evento de clique para exibir modal com dados da parcela
if (!window._parcelasModalHandlerAdded) {
    window._parcelasModalHandlerAdded = true;
    map.on('singleclick', function(evt) {
        map.forEachFeatureAtPixel(evt.pixel, function(feature) {
            if (feature && feature.get('parcela_co')) {
                // Montar HTML do modal
                const props = feature.getProperties();
                let html = `<div class='odsgeo-modal-bg' style='position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);z-index:10000;display:flex;align-items:center;justify-content:center;'>`;
                html += `<div class='odsgeo-modal' style='background:#fff;border-radius:12px;box-shadow:0 8px 32px #0005;max-width:420px;width:96vw;padding:2rem 1.5rem;position:relative;'>`;
                html += `<button onclick='this.closest(".odsgeo-modal-bg").remove()' style='position:absolute;top:12px;right:12px;background:none;border:none;font-size:1.5rem;line-height:1;color:#888;cursor:pointer;'>&times;</button>`;
                html += `<h2 style='font-size:1.2rem;font-weight:700;margin-bottom:1rem;color:#e11d48;'>Dados da Parcela</h2>`;
                html += `<div style='display:grid;grid-template-columns:1fr 1fr;gap:0.5rem 1rem;'>`;
                const campos = [
                    ['parcela_co', 'Código Parcela'],
                    ['rt', 'RT'],
                    ['art', 'ART'],
                    ['situacao_i', 'Situação'],
                    ['codigo_imo', 'Código Imóvel'],
                    ['data_submi', 'Data Submissão'],
                    ['data_aprov', 'Data Aprovação'],
                    ['status', 'Status'],
                    ['nome_area', 'Nome Área'],
                    ['registro_m', 'Registro M'],
                    ['registro_d', 'Registro D'],
                    ['municipio_', 'Município'],
                    ['uf_id', 'UF']
                ];
                campos.forEach(([key, label]) => {
                    html += `<div style='font-weight:600;color:#444;'>${label}:</div><div style='color:#222;'>${props[key] || '-'}</div>`;
                });
                html += `</div></div></div>`;
                // Remove modal anterior se existir
                document.querySelectorAll('.odsgeo-modal-bg').forEach(e => e.remove());
                document.body.insertAdjacentHTML('beforeend', html);
            }
        });
    });
}
</script>
@endpush 