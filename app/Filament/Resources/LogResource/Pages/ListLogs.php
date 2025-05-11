<?php

namespace App\Filament\Resources\LogResource\Pages;

use App\Filament\Resources\LogResource;
use App\Models\Log;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;

class ListLogs extends ListRecords
{
    protected static string $resource = LogResource::class;

    public function mount(): void
    {
        // Removido syncLogs para evitar duplicação
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncLogs')
                ->label('Sincronizar Logs')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->syncLogs();
                    Notification::make()
                        ->title('Logs sincronizados com sucesso')
                        ->success()
                        ->send();
                }),

            Action::make('clearLogs')
                ->label('Limpar Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Log::truncate();
                    File::put(storage_path('logs/laravel.log'), '');
                    Notification::make()
                        ->title('Logs limpos com sucesso')
                        ->success()
                        ->send();
                }),
            
            Action::make('copyLastError')
                ->label('Copiar Último Erro')
                ->icon('heroicon-o-clipboard')
                ->action(function () {
                    $lastError = Log::where('level', 'ERROR')
                        ->latest()
                        ->first();
                    
                    if ($lastError) {
                        $this->js("navigator.clipboard.writeText(`{$lastError->message}`)");
                        Notification::make()
                            ->title('Último erro copiado para a área de transferência')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Nenhum erro encontrado')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }

    protected function syncLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            $content = File::get($logPath);
            $lines = explode("\n", $content);
            
            $currentLog = [];
            foreach ($lines as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    if (!empty($currentLog)) {
                        $this->createLogEntry($currentLog);
                    }
                    $currentLog = [$line];
                } else {
                    $currentLog[] = $line;
                }
            }
            
            if (!empty($currentLog)) {
                $this->createLogEntry($currentLog);
            }
        }
    }

    protected function createLogEntry(array $lines): void
    {
        $firstLine = $lines[0];
        preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] local\.(\w+):/', $firstLine, $matches);
        
        if (isset($matches[1], $matches[2])) {
            $date = $matches[1];
            $level = strtoupper($matches[2]);
            $message = implode("\n", $lines);

            if (!\App\Models\Log::where('date', $date)
                ->where('level', $level)
                ->where('message', $message)
                ->exists()) {
                \App\Models\Log::create([
                    'date' => $date,
                    'level' => $level,
                    'message' => $message,
                ]);
            }
        }
    }

    public static function getResource(): string
    {
        return LogResource::class;
    }
} 