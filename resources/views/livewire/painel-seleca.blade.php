<div>
    <div class="flex justify-center mt-8 gap-6">
        <!-- Cliente -->
        <div class="flex flex-col items-start w-72">
            <label for="cliente" class="block mb-2 text-sm font-bold text-gray-200">Cliente</label>
            <select wire:model.lazy="cliente_id" id="cliente"
                class="rounded-lg px-4 py-0.4 w-full focus:outline-none focus:ring-2 focus:ring-primary-500 transition shadow-sm">
                <option value="">Selecione...</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                @endforeach
            </select>
        </div>
        <!-- Projeto -->
        <div class="flex flex-col items-start w-72">
            <label for="projeto" class="block mb-2 text-sm font-bold text-gray-200">Projeto</label>
            <select wire:model.lazy="projeto_id" id="projeto"
                class="rounded-lg px-4 py-0.4 w-full focus:outline-none focus:ring-2 focus:ring-primary-500 transition shadow-sm">
                @if(!$cliente_id)
                    <option value="">Selecione um cliente</option>
                @else
                    <option value="">Selecione...</option>
                    @foreach($projetos as $projeto)
                        <option value="{{ $projeto->id }}">{{ $projeto->nome }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <!-- Serviço -->
        <div class="flex flex-col items-start w-72">
            <label for="servico" class="block mb-2 text-sm font-bold text-gray-200">Serviço</label>
            <select wire:model.lazy="servico_id" id="servico"
                class="rounded-lg px-4 py-0.4 w-full focus:outline-none focus:ring-2 focus:ring-primary-500 transition shadow-sm">
                @if(!$projeto_id)
                    <option value="">Selecione um projeto</option>
                @else
                    <option value="">Selecione...</option>
                    @foreach($servicos as $servico)
                        <option value="{{ $servico->id }}">{{ $servico->nome }}</option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>

    <!-- Exemplo de menu extra bloqueado -->
    <div class="flex justify-center mt-8">
        <button class="px-6 py-2 rounded text-white font-bold"
            style="background: {{ $servico_id ? '#f59e42' : '#444' }}; cursor: {{ $servico_id ? 'pointer' : 'not-allowed' }};"
            @if(!$servico_id) disabled @endif
        >
            Coordenadas
        </button>
    </div>
</div>

@push('styles')
    <style>
        select, option {
            background: #18181b !important;
            color: #f4f4f5 !important;
            border: 1px solid #27272a !important;
            border-radius: 0.5rem !important;
            font-size: 1rem !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
            min-height: 44px !important;
        }
        select:focus {
            outline: 2px solid #3b82f6 !important;
            box-shadow: 0 0 0 2px #3b82f6 !important;
        }
        option {
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
        }
        select option:checked, select option:focus, select option:hover {
            background: #a5c9fa !important;
            color: #18181b !important;
        }
    </style>
@endpush
