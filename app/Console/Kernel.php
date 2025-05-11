<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Limpar planilhas ODS antigas diariamente às 3h da manhã
        $schedule->command('planilhas:limpar --dias=7')->dailyAt('03:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        Commands\SyncLogs::class,
    ];
} 