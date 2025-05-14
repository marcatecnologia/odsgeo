<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;

class ViewGeoServerReports extends Command
{
    protected $signature = 'geoserver:view-reports 
        {--type=all : Tipo de relatório (daily, weekly, monthly, all)}
        {--format=table : Formato de saída (table, json, csv)}
        {--metrics=all : Métricas específicas (server, memory, cpu, disk, requests, cache, gwc, wfs, wms, jai, all)}
        {--sort=timestamp : Campo para ordenação (timestamp, period)}
        {--order=desc : Ordem de classificação (asc, desc)}
        {--limit=10 : Número máximo de relatórios a serem exibidos}';

    protected $description = 'Visualiza os relatórios de performance do GeoServer';

    public function handle()
    {
        $type = $this->option('type');
        $format = $this->option('format');
        $metrics = explode(',', $this->option('metrics'));
        $sort = $this->option('sort');
        $order = $this->option('order');
        $limit = $this->option('limit');

        $this->info("Visualizando relatórios de performance do GeoServer...");
        $this->info("Tipo: {$type}");
        $this->info("Formato: {$format}");
        $this->info("Métricas: " . implode(', ', $metrics));
        $this->info("Ordenação: {$sort} {$order}");
        $this->info("Limite: {$limit}");

        try {
            $reports = $this->getReports($type, $sort, $order, $limit);
            $this->displayReports($reports, $format, $metrics);
        } catch (\Exception $e) {
            $this->error('Erro ao visualizar relatórios: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function getReports($type, $sort, $order, $limit)
    {
        $files = Storage::files('reports');
        $reports = collect();

        $progress = new ProgressBar($this->output, count($files));
        $progress->start();

        foreach ($files as $file) {
            if (str_ends_with($file, '.json')) {
                $content = Storage::get($file);
                $report = json_decode($content, true);

                if ($this->isReportType($report, $type)) {
                    $reports->push($report);
                }
            }
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();

        return $reports->sortBy($sort, SORT_REGULAR, $order === 'desc')->take($limit);
    }

    protected function isReportType($report, $type)
    {
        if ($type === 'all') {
            return true;
        }

        $period = $report['period'] ?? 0;

        switch ($type) {
            case 'daily':
                return $period === 24;
            case 'weekly':
                return $period === 168;
            case 'monthly':
                return $period === 720;
            default:
                return false;
        }
    }

    protected function displayReports(Collection $reports, $format, $metrics)
    {
        if ($reports->isEmpty()) {
            $this->info('Nenhum relatório encontrado.');
            return;
        }

        switch ($format) {
            case 'json':
                $this->displayJson($reports, $metrics);
                break;
            case 'csv':
                $this->displayCsv($reports, $metrics);
                break;
            default:
                $this->displayTable($reports, $metrics);
        }
    }

    protected function displayJson($reports, $metrics)
    {
        $data = $reports->map(function ($report) use ($metrics) {
            return $this->filterMetrics($report, $metrics);
        });

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function displayCsv($reports, $metrics)
    {
        $headers = ['timestamp', 'period'];
        foreach ($metrics as $metric) {
            if ($metric === 'all') {
                $headers = array_merge($headers, array_keys($reports->first()));
                break;
            }
            $headers = array_merge($headers, array_keys($reports->first()[$metric] ?? []));
        }

        $this->line(implode(',', $headers));

        foreach ($reports as $report) {
            $row = [$report['timestamp'], $report['period']];
            foreach ($metrics as $metric) {
                if ($metric === 'all') {
                    foreach ($report as $key => $value) {
                        if (!in_array($key, ['timestamp', 'period'])) {
                            $row[] = is_array($value) ? json_encode($value) : $value;
                        }
                    }
                    break;
                }
                foreach ($report[$metric] ?? [] as $value) {
                    $row[] = is_array($value) ? json_encode($value) : $value;
                }
            }
            $this->line(implode(',', $row));
        }
    }

    protected function displayTable($reports, $metrics)
    {
        foreach ($reports as $report) {
            $this->displayReportTable($report, $metrics);
        }
    }

    protected function displayReportTable($report, $metrics)
    {
        $this->newLine();
        $this->info('Relatório gerado em: ' . $report['timestamp']);
        $this->info('Período: ' . $report['period'] . ' horas');
        $this->newLine();

        foreach ($metrics as $metric) {
            if ($metric === 'all') {
                $this->displayAllMetrics($report);
                break;
            }
            $this->displayMetricTable($metric, $report[$metric] ?? []);
        }

        $this->newLine();
        $this->line(str_repeat('-', 80));
    }

    protected function displayAllMetrics($report)
    {
        $metrics = ['server', 'memory', 'cpu', 'disk', 'requests', 'cache', 'gwc', 'wfs', 'wms', 'jai'];
        foreach ($metrics as $metric) {
            $this->displayMetricTable($metric, $report[$metric] ?? []);
        }
    }

    protected function displayMetricTable($metric, $data)
    {
        if (empty($data)) {
            return;
        }

        $this->info(ucfirst($metric) . ':');
        $table = new Table($this->output);
        $table->setHeaders(['Métrica', 'Valor']);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_numeric($value) && strpos($key, 'bytes') !== false) {
                $value = $this->formatBytes($value);
            }
            $table->addRow([$key, $value]);
        }

        $table->render();
        $this->newLine();
    }

    protected function filterMetrics($report, $metrics)
    {
        if (in_array('all', $metrics)) {
            return $report;
        }

        $filtered = [
            'timestamp' => $report['timestamp'],
            'period' => $report['period']
        ];

        foreach ($metrics as $metric) {
            if (isset($report[$metric])) {
                $filtered[$metric] = $report[$metric];
            }
        }

        return $filtered;
    }

    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 