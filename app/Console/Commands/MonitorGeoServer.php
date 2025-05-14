<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MonitorGeoServer extends Command
{
    protected $signature = 'geoserver:monitor {--interval=60 : Intervalo de monitoramento em segundos}';
    protected $description = 'Monitora a performance do GeoServer';

    protected $baseUrl;
    protected $username;
    protected $password;
    protected $workspace;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('geoserver.url');
        $this->username = config('geoserver.username');
        $this->password = config('geoserver.password');
        $this->workspace = config('geoserver.workspace');
    }

    public function handle()
    {
        $interval = $this->option('interval');
        $this->info("Iniciando monitoramento do GeoServer (intervalo: {$interval}s)...");

        while (true) {
            try {
                $this->monitor();
                sleep($interval);
            } catch (\Exception $e) {
                $this->error('Erro durante o monitoramento: ' . $e->getMessage());
                Log::error('Erro no monitoramento do GeoServer', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                sleep($interval);
            }
        }
    }

    protected function monitor()
    {
        // 1. Monitorar status do servidor
        $this->monitorServerStatus();

        // 2. Monitorar uso de memória
        $this->monitorMemoryUsage();

        // 3. Monitorar uso de CPU
        $this->monitorCPUUsage();

        // 4. Monitorar uso de disco
        $this->monitorDiskUsage();

        // 5. Monitorar requisições
        $this->monitorRequests();

        // 6. Monitorar cache
        $this->monitorCache();

        // 7. Monitorar GWC
        $this->monitorGWC();

        // 8. Monitorar WFS
        $this->monitorWFS();

        // 9. Monitorar WMS
        $this->monitorWMS();

        // 10. Monitorar JAI
        $this->monitorJAI();
    }

    protected function monitorServerStatus()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/about/status");

        if ($response->successful()) {
            $status = $response->json();
            $this->info('Status do servidor: ' . $status['status']);
            Cache::put('geoserver_status', $status, 60);
        }
    }

    protected function monitorMemoryUsage()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/about/memory");

        if ($response->successful()) {
            $memory = $response->json();
            $this->info('Uso de memória: ' . $memory['used'] . ' / ' . $memory['total']);
            Cache::put('geoserver_memory', $memory, 60);
        }
    }

    protected function monitorCPUUsage()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/about/cpu");

        if ($response->successful()) {
            $cpu = $response->json();
            $this->info('Uso de CPU: ' . $cpu['usage'] . '%');
            Cache::put('geoserver_cpu', $cpu, 60);
        }
    }

    protected function monitorDiskUsage()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/about/disk");

        if ($response->successful()) {
            $disk = $response->json();
            $this->info('Uso de disco: ' . $disk['used'] . ' / ' . $disk['total']);
            Cache::put('geoserver_disk', $disk, 60);
        }
    }

    protected function monitorRequests()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/about/requests");

        if ($response->successful()) {
            $requests = $response->json();
            $this->info('Requisições: ' . $requests['total'] . ' (ativas: ' . $requests['active'] . ')');
            Cache::put('geoserver_requests', $requests, 60);
        }
    }

    protected function monitorCache()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/resource/cache");

        if ($response->successful()) {
            $cache = $response->json();
            $this->info('Cache: ' . $cache['size'] . ' / ' . $cache['maxSize']);
            Cache::put('geoserver_cache', $cache, 60);
        }
    }

    protected function monitorGWC()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/resource/gwc");

        if ($response->successful()) {
            $gwc = $response->json();
            $this->info('GWC: ' . $gwc['status']);
            Cache::put('geoserver_gwc', $gwc, 60);
        }
    }

    protected function monitorWFS()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/services/wfs");

        if ($response->successful()) {
            $wfs = $response->json();
            $this->info('WFS: ' . $wfs['status']);
            Cache::put('geoserver_wfs', $wfs, 60);
        }
    }

    protected function monitorWMS()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/services/wms");

        if ($response->successful()) {
            $wms = $response->json();
            $this->info('WMS: ' . $wms['status']);
            Cache::put('geoserver_wms', $wms, 60);
        }
    }

    protected function monitorJAI()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/rest/resource/jai");

        if ($response->successful()) {
            $jai = $response->json();
            $this->info('JAI: ' . $jai['status']);
            Cache::put('geoserver_jai', $jai, 60);
        }
    }
} 