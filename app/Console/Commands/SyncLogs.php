<?php

namespace App\Console\Commands;

use App\Models\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncLogs extends Command
{
    protected $signature = 'logs:sync';
    protected $description = 'Sincroniza os logs do arquivo laravel.log para o banco de dados';

    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->error('Arquivo de log não encontrado!');
            return 1;
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        
        $currentLog = [];
        $count = 0;
        
        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                if (!empty($currentLog)) {
                    $this->createLogEntry($currentLog);
                    $count++;
                }
                $currentLog = [$line];
            } else {
                $currentLog[] = $line;
            }
        }
        
        if (!empty($currentLog)) {
            $this->createLogEntry($currentLog);
            $count++;
        }

        $this->info("Logs sincronizados com sucesso! {$count} logs processados.");
        return 0;
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
} 