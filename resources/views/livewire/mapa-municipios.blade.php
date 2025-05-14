<div>
    <div class="mb-4">
        <select wire:model.live="ufSelecionada" class="form-select">
            <option value="">Selecione uma UF</option>
            <option value="AC">Acre</option>
            <option value="AL">Alagoas</option>
            <option value="AP">Amapá</option>
            <option value="AM">Amazonas</option>
            <option value="BA">Bahia</option>
            <option value="CE">Ceará</option>
            <option value="DF">Distrito Federal</option>
            <option value="ES">Espírito Santo</option>
            <option value="GO">Goiás</option>
            <option value="MA">Maranhão</option>
            <option value="MT">Mato Grosso</option>
            <option value="MS">Mato Grosso do Sul</option>
            <option value="MG">Minas Gerais</option>
            <option value="PA">Pará</option>
            <option value="PB">Paraíba</option>
            <option value="PR">Paraná</option>
            <option value="PE">Pernambuco</option>
            <option value="PI">Piauí</option>
            <option value="RJ">Rio de Janeiro</option>
            <option value="RN">Rio Grande do Norte</option>
            <option value="RS">Rio Grande do Sul</option>
            <option value="RO">Rondônia</option>
            <option value="RR">Roraima</option>
            <option value="SC">Santa Catarina</option>
            <option value="SP">São Paulo</option>
            <option value="SE">Sergipe</option>
            <option value="TO">Tocantins</option>
        </select>
    </div>

    @if($carregando)
        <div class="alert alert-info">
            Carregando municípios...
        </div>
    @endif

    @if($erro)
        <div class="alert alert-danger">
            {{ $erro }}
            <button wire:click="carregarMunicipios({{ $ufSelecionada }})" class="btn btn-sm btn-danger ms-2">
                Tentar novamente
            </button>
        </div>
    @endif

    @if($ufSelecionada && !$carregando && !$erro)
        <div class="mb-4">
            <select wire:model.live="municipioSelecionado" class="form-select">
                <option value="">Selecione um município</option>
                @foreach($municipios as $municipio)
                    <option value="{{ $municipio['codigo'] }}">{{ $municipio['nome'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div id="mapa" style="height: 600px;"></div>
</div>

@push('scripts')
<script>
    let map;
    let municipiosLayer;
    let markersLayer;

    document.addEventListener('livewire:initialized', () => {
        // Inicializar o mapa
        map = L.map('mapa').setView([-15.7801, -47.9292], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Criar camada de marcadores
        markersLayer = L.layerGroup().addTo(map);

        // Observar mudanças no estado do Livewire
        Livewire.on('municipiosCarregados', (data) => {
            if (municipiosLayer) {
                map.removeLayer(municipiosLayer);
            }
            markersLayer.clearLayers();

            if (data.municipios && data.municipios.length > 0) {
                const geojson = {
                    type: 'FeatureCollection',
                    features: data.municipios.map(mun => ({
                        type: 'Feature',
                        properties: {
                            codigo_ibge: mun.codigo,
                            nome: mun.nome
                        },
                        geometry: mun.geometry
                    }))
                };

                municipiosLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#3388ff',
                        weight: 2,
                        fillOpacity: 0.1
                    },
                    onEachFeature: function(feature, layer) {
                        layer.bindPopup(feature.properties.nome);
                    }
                }).addTo(map);

                // Centralizar no estado
                if (data.centroideEstado) {
                    map.setView([data.centroideEstado.lat, data.centroideEstado.lng], 7);
                } else {
                    map.fitBounds(municipiosLayer.getBounds());
                }
            }
        });

        Livewire.on('municipioSelecionado', (data) => {
            if (data.municipio) {
                const geojson = {
                    type: 'Feature',
                    properties: {
                        codigo_ibge: data.municipio.codigo,
                        nome: data.municipio.nome
                    },
                    geometry: data.municipio.geometry
                };

                if (municipiosLayer) {
                    map.removeLayer(municipiosLayer);
                }
                markersLayer.clearLayers();

                municipiosLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#3388ff',
                        weight: 2,
                        fillOpacity: 0.1
                    }
                }).addTo(map);

                // Adicionar marcador no centróide
                if (data.municipio.centroide) {
                    const marker = L.marker([
                        data.municipio.centroide[1],
                        data.municipio.centroide[0]
                    ]).bindPopup(data.municipio.nome);
                    markersLayer.addLayer(marker);
                }

                map.fitBounds(municipiosLayer.getBounds());
            }
        });
    });
</script>
@endpush 