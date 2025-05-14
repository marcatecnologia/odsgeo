<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ScheduleGeoServerMonitoring extends Command
{
    protected $signature = 'geoserver:schedule-monitoring';
    protected $description = 'Agenda o monitoramento e geração de relatórios do GeoServer';

    public function handle()
    {
        $this->info('Agendando monitoramento e geração de relatórios do GeoServer...');

        try {
            // 1. Agendar monitoramento a cada 5 minutos
            $this->scheduleMonitoring();

            // 2. Agendar geração de relatórios
            $this->scheduleReports();

            $this->info('Monitoramento e geração de relatórios agendados com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao agendar monitoramento e geração de relatórios: ' . $e->getMessage());
            Log::error('Erro ao agendar monitoramento e geração de relatórios do GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function scheduleMonitoring()
    {
        $this->info('Agendando monitoramento...');

        // Executar monitoramento a cada 5 minutos
        $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
        $schedule->command('geoserver:monitor', ['--interval' => 300])
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $this->info('Monitoramento agendado com sucesso.');
    }

    protected function scheduleReports()
    {
        $this->info('Agendando geração de relatórios...');

        $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);

        // Relatório diário (últimas 24 horas)
        $schedule->command('geoserver:report', ['--period' => 24])
            ->daily()
            ->at('00:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Relatório semanal (últimas 168 horas)
        $schedule->command('geoserver:report', ['--period' => 168])
            ->weekly()
            ->mondays()
            ->at('00:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Relatório mensal (últimas 720 horas)
        $schedule->command('geoserver:report', ['--period' => 720])
            ->monthly()
            ->firstOfMonth()
            ->at('00:00')
            ->withoutOverlapping()
            ->runInBackground();

        $this->info('Geração de relatórios agendada com sucesso.');
    }
} 