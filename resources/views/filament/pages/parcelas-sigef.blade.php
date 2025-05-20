<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Visualização de Parcelas SIGEF
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Selecione um estado e município para visualizar as parcelas SIGEF no mapa.
            </p>
        </div>

        <livewire:mapa-parcelas-sigef />
    </div>
</x-filament-panels::page>
