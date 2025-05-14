<div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="uf">Estado (UF)</label>
                <select wire:model="uf" id="uf" class="form-control" wire:change="$emit('ufSelected', $event.target.value)">
                    <option value="">Selecione um estado</option>
                    @foreach(['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 
                            'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 
                            'SP', 'SE', 'TO'] as $ufOption)
                        <option value="{{ $ufOption }}">{{ $ufOption }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="municipio">Município</label>
                <select wire:model="municipio" id="municipio" class="form-control" 
                        wire:change="$emit('municipioSelected', $event.target.value)"
                        {{ empty($municipios) ? 'disabled' : '' }}>
                    <option value="">Selecione um município</option>
                    @foreach($municipios as $mun)
                        <option value="{{ $mun['codigo_ibge'] }}">{{ $mun['nome'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($error)
        <div class="alert alert-danger mt-3">
            {{ $error }}
            <button wire:click="handleUfSelected('{{ $uf }}')" class="btn btn-sm btn-danger ml-2">
                Tentar novamente
            </button>
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            // Listener para quando a UF é selecionada
            Livewire.on('ufSelected', (uf) => {
                if (uf) {
                    // Limpar camada de municípios anterior
                    if (window.municipiosLayer) {
                        map.removeLayer(window.municipiosLayer);
                    }
                }
            });

            // Listener para quando os municípios são carregados
            Livewire.hook('message.processed', (message, component) => {
                if (message.updateQueue.length > 0 && component.municipios.length > 0) {
                    // Criar GeoJSON com os municípios carregados
                    const geojson = {
                        type: 'FeatureCollection',
                        features: component.municipios.map(mun => ({
                            type: 'Feature',
                            properties: {
                                codigo_ibge: mun.codigo_ibge,
                                nome: mun.nome
                            },
                            geometry: mun.geometry
                        }))
                    };

                    // Adicionar camada ao mapa
                    window.municipiosLayer = L.geoJSON(geojson, {
                        style: {
                            color: '#3388ff',
                            weight: 2,
                            fillOpacity: 0.1
                        },
                        onEachFeature: function(feature, layer) {
                            layer.bindPopup(feature.properties.nome);
                        }
                    }).addTo(map);

                    // Centralizar mapa no centróide do estado
                    if (component.centroide) {
                        map.setView([component.centroide.lat, component.centroide.lng], 7);
                    }
                }
            });

            // Listener para quando um município é selecionado
            Livewire.on('municipioSelected', (codigoIbge) => {
                if (codigoIbge && window.municipiosLayer) {
                    // Encontrar feature do município
                    const feature = window.municipiosLayer.getLayers().find(layer => 
                        layer.feature.properties.codigo_ibge === codigoIbge
                    );

                    if (feature) {
                        // Destacar município selecionado
                        window.municipiosLayer.resetStyle();
                        feature.setStyle({
                            color: '#ff0000',
                            weight: 3,
                            fillOpacity: 0.3
                        });

                        // Centralizar mapa no município
                        const bounds = feature.getBounds();
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
            });
        });
    </script>
    @endpush
</div> 