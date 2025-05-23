<x-filament-panels::page>
    <div id="map-loader" style="display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; z-index:9999; background:rgba(24,24,28,0.45); backdrop-filter:blur(1.5px); align-items:center; justify-content:center;">
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
    <div class="space-y-6">
        @livewire('mapa-parcelas-sigef')
    </div>
</x-filament-panels::page>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
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
        // Controle do loader será feito manualmente no JS do mapa

        // Loader imediato ao selecionar estado/município
        document.addEventListener('DOMContentLoaded', function () {
            // Loader ao selecionar estado
            const estadoSelect = document.getElementById('estado');
            if (estadoSelect) {
                estadoSelect.addEventListener('change', function () {
                    showMapLoader('Buscando estado...');
                });
            }
            // Loader ao selecionar município
            const municipioSelect = document.getElementById('municipio');
            if (municipioSelect) {
                municipioSelect.addEventListener('change', function () {
                    showMapLoader('Buscando município...');
                });
            }
        });
    </script>
    <style>
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
    </style>
@endpush
