<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class GenerateGeoServerReport extends Command
{
    protected $signature = 'geoserver:report {--period=24 : Período do relatório em horas}';
    protected $description = 'Gera relatório de performance do GeoServer';

    public function handle()
    {
        $period = $this->option('period');
        $this->info("Gerando relatório de performance do GeoServer (período: {$period}h)...");

        try {
            $report = $this->generateReport($period);
            $this->saveReport($report);
            $this->info('Relatório gerado com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao gerar relatório: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function generateReport($period)
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'period' => $period,
            'server' => $this->getServerMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'cpu' => $this->getCPUMetrics(),
            'disk' => $this->getDiskMetrics(),
            'requests' => $this->getRequestMetrics(),
            'cache' => $this->getCacheMetrics(),
            'gwc' => $this->getGWCMetrics(),
            'wfs' => $this->getWFSMetrics(),
            'wms' => $this->getWMSMetrics(),
            'jai' => $this->getJAIMetrics()
        ];

        return $report;
    }

    protected function getServerMetrics()
    {
        $status = Cache::get('geoserver_status');
        return [
            'status' => $status['status'] ?? 'unknown',
            'version' => $status['version'] ?? 'unknown',
            'uptime' => $status['uptime'] ?? 'unknown'
        ];
    }

    protected function getMemoryMetrics()
    {
        $memory = Cache::get('geoserver_memory');
        return [
            'used' => $memory['used'] ?? 0,
            'total' => $memory['total'] ?? 0,
            'free' => $memory['free'] ?? 0,
            'usage' => $memory['usage'] ?? 0
        ];
    }

    protected function getCPUMetrics()
    {
        $cpu = Cache::get('geoserver_cpu');
        return [
            'usage' => $cpu['usage'] ?? 0,
            'cores' => $cpu['cores'] ?? 0,
            'load' => $cpu['load'] ?? 0
        ];
    }

    protected function getDiskMetrics()
    {
        $disk = Cache::get('geoserver_disk');
        return [
            'used' => $disk['used'] ?? 0,
            'total' => $disk['total'] ?? 0,
            'free' => $disk['free'] ?? 0,
            'usage' => $disk['usage'] ?? 0
        ];
    }

    protected function getRequestMetrics()
    {
        $requests = Cache::get('geoserver_requests');
        return [
            'total' => $requests['total'] ?? 0,
            'active' => $requests['active'] ?? 0,
            'completed' => $requests['completed'] ?? 0,
            'failed' => $requests['failed'] ?? 0
        ];
    }

    protected function getCacheMetrics()
    {
        $cache = Cache::get('geoserver_cache');
        return [
            'size' => $cache['size'] ?? 0,
            'maxSize' => $cache['maxSize'] ?? 0,
            'hits' => $cache['hits'] ?? 0,
            'misses' => $cache['misses'] ?? 0
        ];
    }

    protected function getGWCMetrics()
    {
        $gwc = Cache::get('geoserver_gwc');
        return [
            'status' => $gwc['status'] ?? 'unknown',
            'layers' => $gwc['layers'] ?? 0,
            'tiles' => $gwc['tiles'] ?? 0,
            'diskUsage' => $gwc['diskUsage'] ?? 0
        ];
    }

    protected function getWFSMetrics()
    {
        $wfs = Cache::get('geoserver_wfs');
        return [
            'status' => $wfs['status'] ?? 'unknown',
            'requests' => $wfs['requests'] ?? 0,
            'errors' => $wfs['errors'] ?? 0,
            'avgResponseTime' => $wfs['avgResponseTime'] ?? 0
        ];
    }

    protected function getWMSMetrics()
    {
        $wms = Cache::get('geoserver_wms');
        return [
            'status' => $wms['status'] ?? 'unknown',
            'requests' => $wms['requests'] ?? 0,
            'errors' => $wms['errors'] ?? 0,
            'avgResponseTime' => $wms['avgResponseTime'] ?? 0
        ];
    }

    protected function getJAIMetrics()
    {
        $jai = Cache::get('geoserver_jai');
        return [
            'status' => $jai['status'] ?? 'unknown',
            'operations' => $jai['operations'] ?? 0,
            'errors' => $jai['errors'] ?? 0,
            'avgResponseTime' => $jai['avgResponseTime'] ?? 0
        ];
    }

    protected function saveReport($report)
    {
        $filename = 'geoserver_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        Storage::put('reports/' . $filename, json_encode($report, JSON_PRETTY_PRINT));
    }
} 