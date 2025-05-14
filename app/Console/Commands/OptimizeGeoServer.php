<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OptimizeGeoServer extends Command
{
    protected $signature = 'geoserver:optimize';
    protected $description = 'Otimiza a performance do GeoServer';

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
        $this->info('Otimizando performance do GeoServer...');

        try {
            // 1. Configurar cache
            $this->configureCache();

            // 2. Configurar GWC
            $this->configureGWC();

            // 3. Configurar JAI
            $this->configureJAI();

            // 4. Configurar WFS
            $this->configureWFS();

            // 5. Configurar WMS
            $this->configureWMS();

            $this->info('Otimização do GeoServer concluída com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante a otimização do GeoServer: ' . $e->getMessage());
            Log::error('Erro na otimização do GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function configureCache()
    {
        $this->info('Configurando cache...');

        $cacheConfig = [
            'cache' => [
                'enabled' => true,
                'maxSize' => 512,
                'maxAge' => 3600,
                'maxIdle' => 300
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/resource/cache", $cacheConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar cache: ' . $response->body());
        }

        $this->info('Cache configurado com sucesso.');
    }

    protected function configureGWC()
    {
        $this->info('Configurando GeoWebCache...');

        $gwcConfig = [
            'gwc' => [
                'enabled' => true,
                'cacheLayersByDefault' => true,
                'defaultCachingEnabled' => true,
                'defaultCacheFormats' => ['image/png', 'image/jpeg'],
                'defaultExpireCache' => 3600,
                'defaultExpireClients' => 300,
                'defaultExpireMetadata' => 3600,
                'defaultExpireMinZoom' => 0,
                'defaultExpireMaxZoom' => 20,
                'defaultExpireGridsets' => ['EPSG:4326', 'EPSG:3857']
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/resource/gwc", $gwcConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar GeoWebCache: ' . $response->body());
        }

        $this->info('GeoWebCache configurado com sucesso.');
    }

    protected function configureJAI()
    {
        $this->info('Configurando JAI...');

        $jaiConfig = [
            'jai' => [
                'enabled' => true,
                'allowNative' => true,
                'allowNativeMosaic' => true,
                'allowNativeWarp' => true,
                'allowNativeColorOps' => true,
                'allowNativeScale' => true,
                'allowNativeAffine' => true,
                'allowNativeCrop' => true,
                'allowNativeBandSelect' => true,
                'allowNativeBandMerge' => true,
                'allowNativeFormat' => true,
                'allowNativeLookup' => true,
                'allowNativeStats' => true,
                'allowNativeHistogram' => true,
                'allowNativeContrast' => true,
                'allowNativeThreshold' => true,
                'allowNativeConvolve' => true,
                'allowNativeGradient' => true,
                'allowNativeTranspose' => true,
                'allowNativeRotate' => true,
                'allowNativeShear' => true,
                'allowNativeTranslate' => true,
                'allowNativeScale' => true,
                'allowNativeAffine' => true,
                'allowNativeCrop' => true,
                'allowNativeBandSelect' => true,
                'allowNativeBandMerge' => true,
                'allowNativeFormat' => true,
                'allowNativeLookup' => true,
                'allowNativeStats' => true,
                'allowNativeHistogram' => true,
                'allowNativeContrast' => true,
                'allowNativeThreshold' => true,
                'allowNativeConvolve' => true,
                'allowNativeGradient' => true,
                'allowNativeTranspose' => true,
                'allowNativeRotate' => true,
                'allowNativeShear' => true,
                'allowNativeTranslate' => true
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/resource/jai", $jaiConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar JAI: ' . $response->body());
        }

        $this->info('JAI configurado com sucesso.');
    }

    protected function configureWFS()
    {
        $this->info('Configurando WFS...');

        $wfsConfig = [
            'wfs' => [
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
                'maxNumberOfFeaturesForPreview' => 50,
                'serviceLevel' => 'COMPLETE'
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/services/wfs/settings", $wfsConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar WFS: ' . $response->body());
        }

        $this->info('WFS configurado com sucesso.');
    }

    protected function configureWMS()
    {
        $this->info('Configurando WMS...');

        $wmsConfig = [
            'wms' => [
                'enabled' => true,
                'watermark' => [
                    'enabled' => false
                ],
                'interpolation' => [
                    'default' => 'Bicubic'
                ],
                'getFeatureInfo' => [
                    'enabled' => true,
                    'gml' => [
                        'srsNameStyle' => 'URN'
                    ]
                ],
                'getMap' => [
                    'enabled' => true,
                    'mimeTypes' => [
                        'mimeType' => [
                            'image/png',
                            'image/jpeg',
                            'image/gif',
                            'application/pdf'
                        ]
                    ]
                ],
                'getLegendGraphic' => [
                    'enabled' => true
                ],
                'describeLayer' => [
                    'enabled' => true
                ],
                'getStyles' => [
                    'enabled' => true
                ],
                'putStyles' => [
                    'enabled' => true
                ]
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/services/wms/settings", $wmsConfig);

        if (!$response->successful()) {
            throw new \Exception('Erro ao configurar WMS: ' . $response->body());
        }

        $this->info('WMS configurado com sucesso.');
    }
} 