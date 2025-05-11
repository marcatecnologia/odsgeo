<x-filament-panels::page>
    <div class="space-y-4">
        @foreach($this->getLogs() as $log)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm text-gray-500">
                        {{ $log['date'] }}
                    </div>
                    <div>
                        <x-filament::badge
                            :color="match($log['level']) {
                                'ERROR' => 'danger',
                                'WARNING' => 'warning',
                                default => 'success',
                            }"
                        >
                            {{ $log['level'] }}
                        </x-filament::badge>
                    </div>
                </div>
                
                <div class="bg-gray-100 dark:bg-gray-900 rounded p-2">
                    <pre class="text-sm whitespace-pre-wrap">{{ $log['message'] }}</pre>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page> 