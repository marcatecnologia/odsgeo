<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SyncLogs::class,
        Commands\AtualizarMunicipios::class,
        Commands\AtualizarMunicipiosRapido::class,
        Commands\DividirGeoJSONMunicipios::class,
        Commands\BaixarGeoJSONMunicipios::class,
        \App\Console\Commands\DiagnosticoGeoServerCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Sincroniza as camadas WMS diariamente às 3h da manhã
        $schedule->command('sigef:sincronizar-camadas-wms')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 