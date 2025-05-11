<x-filament::modal
    id="selecionar-diretorio-modal"
    width="2xl"
    :heading="__('Selecionar Diretório')"
>
    <div class="space-y-4">
        <!-- Cliente -->
        <div class="space-y-2">
            <label for="cliente" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Cliente
            </label>
            <div class="flex gap-2">
                <select
                    wire:model.live="cliente_id"
                    id="cliente"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Selecione um cliente...</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                    @endforeach
                </select>
                <a href="/admin/clientes/create" target="_blank" class="px-3 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" title="Novo Cliente">+
                </a>
                <button type="button" wire:click="atualizarClientes" class="px-3 py-2 rounded bg-gray-600 text-white hover:bg-gray-700 transition" title="Recarregar Clientes">
                    &#x21bb;
                </button>
            </div>
        </div>

        <!-- Projeto -->
        <div class="space-y-2">
            <label for="projeto" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Projeto
            </label>
            <div class="flex gap-2">
                <select
                    wire:model.live="projeto_id"
                    id="projeto"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500"
                    @if(!$cliente_id) disabled @endif
                >
                    <option value="">Selecione um projeto...</option>
                    @foreach($projetos as $projeto)
                        <option value="{{ $projeto->id }}">{{ $projeto->nome }}</option>
                    @endforeach
                </select>
                <a href="/admin/projetos/create" target="_blank" class="px-3 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" title="Novo Projeto">+
                </a>
                <button type="button" wire:click="atualizarProjetos" class="px-3 py-2 rounded bg-gray-600 text-white hover:bg-gray-700 transition" title="Recarregar Projetos">
                    &#x21bb;
                </button>
            </div>
        </div>

        <!-- Serviço -->
        <div class="space-y-2">
            <label for="servico" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Serviço
            </label>
            <div class="flex gap-2">
                <select
                    wire:model.live="servico_id"
                    id="servico"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500"
                    @if(!$projeto_id) disabled @endif
                >
                    <option value="">Selecione um serviço...</option>
                    @foreach($servicos as $servico)
                        <option value="{{ $servico->id }}">{{ $servico->nome }}</option>
                    @endforeach
                </select>
                <a href="/admin/servicos/create" target="_blank" class="px-3 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" title="Novo Serviço">+
                </a>
                <button type="button" wire:click="atualizarServicos" class="px-3 py-2 rounded bg-gray-600 text-white hover:bg-gray-700 transition" title="Recarregar Serviços">
                    &#x21bb;
                </button>
            </div>
        </div>
    </div>

    <x-slot name="footerActions">
        <x-filament::button
            wire:click="confirmarSelecao"
            color="primary"
        >
            Confirmar Seleção
        </x-filament::button>
    </x-slot>
</x-filament::modal>

@push('scripts')
<script>
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            Livewire.dispatch('atualizarTodos');
        }
    });
</script>
@endpush 