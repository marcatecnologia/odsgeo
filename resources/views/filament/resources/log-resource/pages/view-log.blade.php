@php
    $preId = 'log-message-content-' . ($log->id ?? uniqid());
@endphp

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex flex-col gap-2">
            <div>
                <span class="text-sm font-medium text-gray-500">Data:</span>
                <span class="ml-2">{{ $log->date->format('d/m/Y H:i:s') }}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Nível:</span>
                <span class="ml-2">
                    <x-filament::badge
                        :color="match($log->level) {
                            'ERROR' => 'danger',
                            'WARNING' => 'warning',
                            default => 'success',
                        }"
                    >
                        {{ $log->level }}
                    </x-filament::badge>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-white rounded transition"
                style="background-color: #f59e42;"
                data-copy-btn="{{ $preId }}"
                title="Copiar mensagem completa"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                Copiar
            </button>
        </div>
    </div>

    <div>
        <span class="text-sm font-medium text-gray-500">Mensagem:</span>
        <div class="mt-2 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto max-h-[60vh]">
            <pre id="{{ $preId }}" class="whitespace-pre-wrap text-sm font-mono leading-relaxed">{{ $log->message }}</pre>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-copy-btn="{{ $preId }}"]').forEach(function(btn) {
            btn.onclick = function(event) {
                event.preventDefault();
                event.stopPropagation();
                const text = document.getElementById('{{ $preId }}').innerText;
                navigator.clipboard.writeText(text);
                if (window.Filament && Filament.Notifications) {
                    Filament.Notifications.Notification.make().title('Mensagem copiada!').success().send();
                } else {
                    alert('Mensagem copiada!');
                }
            }
        });
    });
</script> 