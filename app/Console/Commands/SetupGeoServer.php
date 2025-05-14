<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SetupGeoServer extends Command
{
    protected $signature = 'geoserver:setup';
    protected $description = 'Configura o GeoServer para o projeto ODSGeo';

    public function handle()
    {
        $this->info('Iniciando configuração do GeoServer...');

        try {
            // 1. Testar conexão com PostgreSQL
            $this->info('Testando conexão com PostgreSQL...');
            Artisan::call('postgresql:test-connection');
            $this->info(Artisan::output());

            // 2. Testar conexão com GeoServer
            $this->info('Testando conexão com GeoServer...');
            Artisan::call('geoserver:test-connection');
            $this->info(Artisan::output());

            // 3. Configurar workspace e store
            $this->info('Configurando workspace e store...');
            Artisan::call('geoserver:configure-workspace');
            $this->info(Artisan::output());

            // 4. Configurar CORS
            $this->info('Configurando CORS...');
            Artisan::call('geoserver:configure-cors');
            $this->info(Artisan::output());

            // 5. Configurar WFS
            $this->info('Configurando WFS...');
            Artisan::call('geoserver:configure-wfs');
            $this->info(Artisan::output());

            // 6. Publicar camada de municípios
            $this->info('Publicando camada de municípios...');
            Artisan::call('geoserver:publish-municipios');
            $this->info(Artisan::output());

            // 7. Testar camada de municípios
            $this->info('Testando camada de municípios...');
            Artisan::call('geoserver:test-municipios-layer');
            $this->info(Artisan::output());

            // 8. Testar integração com frontend
            $this->info('Testando integração com frontend...');
            Artisan::call('geoserver:test-frontend-integration');
            $this->info(Artisan::output());

            // 9. Testar performance
            $this->info('Testando performance...');
            Artisan::call('geoserver:test-performance');
            $this->info(Artisan::output());

            // 10. Otimizar performance
            $this->info('Otimizando performance...');
            Artisan::call('geoserver:optimize');
            $this->info(Artisan::output());

            // 11. Iniciar monitoramento
            $this->info('Iniciando monitoramento...');
            Artisan::call('geoserver:monitor', ['--interval' => 60]);
            $this->info(Artisan::output());

            // 12. Gerar relatório inicial
            $this->info('Gerando relatório inicial...');
            Artisan::call('geoserver:report', ['--period' => 1]);
            $this->info(Artisan::output());

            // 13. Agendar monitoramento e relatórios
            $this->info('Agendando monitoramento e relatórios...');
            Artisan::call('geoserver:schedule-monitoring');
            $this->info(Artisan::output());

            // 14. Visualizar relatório inicial em formato tabela
            $this->info('Visualizando relatório inicial em formato tabela...');
            Artisan::call('geoserver:view-reports', [
                '--type' => 'all',
                '--format' => 'table',
                '--metrics' => 'all',
                '--sort' => 'timestamp',
                '--order' => 'desc',
                '--limit' => 1
            ]);
            $this->info(Artisan::output());

            // 15. Visualizar relatório inicial em formato JSON
            $this->info('Visualizando relatório inicial em formato JSON...');
            Artisan::call('geoserver:view-reports', [
                '--type' => 'all',
                '--format' => 'json',
                '--metrics' => 'all',
                '--sort' => 'timestamp',
                '--order' => 'desc',
                '--limit' => 1
            ]);
            $this->info(Artisan::output());

            // 16. Visualizar relatório inicial em formato CSV
            $this->info('Visualizando relatório inicial em formato CSV...');
            Artisan::call('geoserver:view-reports', [
                '--type' => 'all',
                '--format' => 'csv',
                '--metrics' => 'all',
                '--sort' => 'timestamp',
                '--order' => 'desc',
                '--limit' => 1
            ]);
            $this->info(Artisan::output());

            $this->info('Configuração do GeoServer concluída com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante a configuração do GeoServer: ' . $e->getMessage());
            Log::error('Erro na configuração do GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
} 