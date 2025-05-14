<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigureGeoServerWFS extends Command
{
    protected $signature = 'geoserver:configure-wfs';
    protected $description = 'Configura o WFS no GeoServer';

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
        $this->info('Configurando WFS no GeoServer...');

        try {
            // 1. Configurar WFS global
            $this->configureGlobalWFS();

            // 2. Configurar WFS do workspace
            $this->configureWorkspaceWFS();

            $this->info('WFS configurado com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao configurar WFS: ' . $e->getMessage());
            Log::error('Erro ao configurar WFS no GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function configureGlobalWFS()
    {
        $this->info('Configurando WFS global...');

        $wfsConfig = [
            'wfs' => [
                'serviceLevel' => 'COMPLETE',
                'gml' => [
                    'srsNameStyle' => 'URN',
                    'overrideGMLAttributes' => false
                ],
                'serviceLevel' => 'COMPLETE',
                'maxFeatures' => 1000000,
                'featureBounding' => true,
                'canonicalSchemaLocation' => false,
                'encodeFeatureMember' => false,
                'hitsIgnoreMaxFeatures' => false,
                'returnDecimalGeometries' => true,
                'allowGlobalQueries' => true,
                'simpleConversionEnabled' => true,
                'maxNumberOfFeaturesForPreview' => 50,
                'serviceLevel' => 'COMPLETE'
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/services/wfs/settings", $wfsConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar WFS global: ' . $response->body());
        }

        $this->info('WFS global configurado com sucesso.');
    }

    protected function configureWorkspaceWFS()
    {
        $this->info('Configurando WFS do workspace...');

        $wfsConfig = [
            'wfs' => [
                'enabled' => true,
                'serviceLevel' => 'COMPLETE',
                'gml' => [
                    'srsNameStyle' => 'URN',
                    'overrideGMLAttributes' => false
                ],
                'maxFeatures' => 1000000,
                'featureBounding' => true,
                'canonicalSchemaLocation' => false,
                'encodeFeatureMember' => false,
                'hitsIgnoreMaxFeatures' => false,
                'returnDecimalGeometries' => true,
                'allowGlobalQueries' => true,
                'simpleConversionEnabled' => true,
                'maxNumberOfFeaturesForPreview' => 50
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/services/wfs/workspaces/{$this->workspace}/settings", $wfsConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar WFS do workspace: ' . $response->body());
        }

        $this->info('WFS do workspace configurado com sucesso.');
    }
} 