<div>
    <x-filament::button
        x-data=""
        x-on:click="$dispatch('open-modal', { id: 'selecionar-diretorio-modal' })"
        color="primary"
        class="flex items-center gap-2"
    >
        <x-heroicon-o-folder class="w-5 h-5" />
        {{ $currentService ? "{$currentService->projeto->cliente->nome} > {$currentService->projeto->nome} > {$currentService->nome}" : 'Selecionar Diretório' }}
    </x-filament::button>

    <livewire:selecionar-diretorio-modal />

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('diretorio-atualizado', () => {
                @this.loadCurrentService();
            });
            Livewire.on('close-modal', () => {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'selecionar-diretorio-modal' } }));
            });
        });
    </script>
</div> 