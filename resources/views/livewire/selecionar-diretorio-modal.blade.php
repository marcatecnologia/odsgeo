<div>
    <x-filament::modal
        id="selecionar-diretorio-modal"
        width="4xl"
        :heading="__('Selecionar Diretório')"
    >
        <div class="p-6 space-y-6">
            <!-- Cliente -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Cliente</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live="searchCliente"
                        placeholder="Buscar cliente..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>
                <select
                    wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Selecione um cliente</option>
                    @forelse($clientes as $cliente)
                        <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                    @empty
                        <option value="" disabled>Nenhum cliente encontrado</option>
                    @endforelse
                </select>
            </div>

            <!-- Projeto -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Projeto</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live="searchProjeto"
                        placeholder="Buscar projeto..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        @if(!$cliente_id) disabled @endif
                    >
                </div>
                <select
                    wire:model.live="projeto_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    @if(!$cliente_id) disabled @endif
                >
                    <option value="">Selecione um projeto</option>
                    @forelse($projetos as $projeto)
                        <option value="{{ $projeto->id }}">{{ $projeto->nome }}</option>
                    @empty
                        <option value="" disabled>Nenhum projeto encontrado</option>
                    @endforelse
                </select>
            </div>

            <!-- Serviço -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Serviço</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live="searchServico"
                        placeholder="Buscar serviço..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        @if(!$projeto_id) disabled @endif
                    >
                </div>
                <select
                    wire:model.live="servico_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    @if(!$projeto_id) disabled @endif
                >
                    <option value="">Selecione um serviço</option>
                    @forelse($servicos as $servico)
                        <option value="{{ $servico->id }}">{{ $servico->nome }}</option>
                    @empty
                        <option value="" disabled>Nenhum serviço encontrado</option>
                    @endforelse
                </select>
            </div>

            <!-- Breadcrumb -->
            <div class="flex items-center space-x-2 text-sm text-gray-500">
                <span>Localização atual:</span>
                @if($cliente_id)
                    <span class="font-medium text-primary-600">
                        {{ $clientes->firstWhere('id', $cliente_id)?->nome }}
                    </span>
                    @if($projeto_id)
                        <x-heroicon-o-chevron-right class="h-4 w-4" />
                        <span class="font-medium text-primary-600">
                            {{ $projetos->firstWhere('id', $projeto_id)?->nome }}
                        </span>
                        @if($servico_id)
                            <x-heroicon-o-chevron-right class="h-4 w-4" />
                            <span class="font-medium text-primary-600">
                                {{ $servicos->firstWhere('id', $servico_id)?->nome }}
                            </span>
                        @endif
                    @endif
                @else
                    <span class="text-gray-400">Nenhum diretório selecionado</span>
                @endif
            </div>

            <!-- Botões -->
            <div class="flex justify-end space-x-3 pt-4">
                <button
                    wire:click="confirmarSelecao"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    @if(!$servico_id) disabled @endif
                >
                    Confirmar
                </button>
                <button
                    wire:click="$dispatch('close-modal')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                    Cancelar
                </button>
            </div>
        </div>
    </x-filament::modal>
</div>

@push('scripts')
<script>
    let tomSelects = {};

    function initTomSelects() {
        const selects = ['cliente', 'projeto', 'servico'];
        
        selects.forEach(id => {
            const el = document.getElementById(id);
            if (el && !tomSelects[id]) {
                tomSelects[id] = new TomSelect(el, {
                    create: false,
                    maxItems: 1,
                    allowEmptyOption: true,
                    searchField: ['text'],
                    controlInput: false,
                    render: {
                        option: function(data, escape) {
                            return `<div>${escape(data.text)}</div>`;
                        }
                    },
                    onInitialize: function() {
                        this.wrapper.classList.add('tomselect-dark');
                    },
                    onChange: function(value) {
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        });
    }

    function destroyTomSelects() {
        Object.values(tomSelects).forEach(ts => {
            if (ts) {
                ts.destroy();
            }
        });
        tomSelects = {};
    }

    document.addEventListener('livewire:initialized', () => {
        initTomSelects();
    });

    document.addEventListener('livewire:navigated', () => {
        destroyTomSelects();
        initTomSelects();
    });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            Livewire.dispatch('atualizarTodos');
        }
    });
</script>
@endpush 