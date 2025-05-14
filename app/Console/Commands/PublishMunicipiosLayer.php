<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishMunicipiosLayer extends Command
{
    protected $signature = 'geoserver:publish-municipios';
    protected $description = 'Publica a camada de municípios no GeoServer';

    protected $baseUrl;
    protected $username;
    protected $password;
    protected $workspace;
    protected $store;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('geoserver.url');
        $this->username = config('geoserver.username');
        $this->password = config('geoserver.password');
        $this->workspace = config('geoserver.workspace');
        $this->store = config('geoserver.store');
    }

    public function handle()
    {
        $this->info('Publicando camada de municípios no GeoServer...');

        try {
            // 1. Publicar camada
            $this->publishLayer();

            // 2. Configurar estilo
            $this->configureStyle();

            $this->info('Camada de municípios publicada com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao publicar camada: ' . $e->getMessage());
            Log::error('Erro ao publicar camada no GeoServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function publishLayer()
    {
        $this->info('Publicando camada...');

        $layerConfig = [
            'featureType' => [
                'name' => 'municipios',
                'nativeName' => 'municipios_simplificado',
                'title' => 'Municípios',
                'srs' => 'EPSG:4326',
                'attributes' => [
                    'attribute' => [
                        [
                            'name' => 'codigo_ibge',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ],
                        [
                            'name' => 'nome',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ],
                        [
                            'name' => 'uf',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'java.lang.String'
                        ],
                        [
                            'name' => 'geom',
                            'minOccurs' => 0,
                            'maxOccurs' => 1,
                            'nillable' => true,
                            'binding' => 'org.locationtech.jts.geom.MultiPolygon'
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/workspaces/{$this->workspace}/datastores/{$this->store}/featuretypes", $layerConfig);

        if (!$response->successful() && $response->status() !== 401) {
            throw new \Exception('Erro ao publicar camada: ' . $response->body());
        }

        $this->info('Camada publicada com sucesso.');
    }

    protected function configureStyle()
    {
        $this->info('Configurando estilo...');

        $styleConfig = [
            'style' => [
                'name' => 'municipios',
                'filename' => 'municipios.sld',
                'format' => 'sld',
                'languageVersion' => '1.0.0',
                'body' => $this->getSLDContent()
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/rest/styles", $styleConfig);

        if (!$response->successful() && $response->status() !== 401) {
            throw new \Exception('Erro ao configurar estilo: ' . $response->body());
        }

        // Aplicar estilo à camada
        $layerStyleConfig = [
            'layer' => [
                'defaultStyle' => [
                    'name' => 'municipios'
                ]
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->put("{$this->baseUrl}/rest/layers/{$this->workspace}:municipios", $layerStyleConfig);

        if (!$response->successful() && $response->status() !== 401) {
            throw new \Exception('Erro ao aplicar estilo à camada: ' . $response->body());
        }

        $this->info('Estilo configurado com sucesso.');
    }

    protected function getSLDContent()
    {
        return <<<SLD
<?xml version="1.0" encoding="UTF-8"?>
<StyledLayerDescriptor version="1.0.0" 
    xmlns="http://www.opengis.net/sld" 
    xmlns:ogc="http://www.opengis.net/ogc"
    xmlns:xlink="http://www.w3.org/1999/xlink" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.opengis.net/sld http://schemas.opengis.net/sld/1.0.0/StyledLayerDescriptor.xsd">
    <NamedLayer>
        <Name>municipios</Name>
        <UserStyle>
            <Title>Municípios</Title>
            <FeatureTypeStyle>
                <Rule>
                    <PolygonSymbolizer>
                        <Fill>
                            <CssParameter name="fill">#FFFFFF</CssParameter>
                            <CssParameter name="fill-opacity">0.5</CssParameter>
                        </Fill>
                        <Stroke>
                            <CssParameter name="stroke">#000000</CssParameter>
                            <CssParameter name="stroke-width">1</CssParameter>
                        </Stroke>
                    </PolygonSymbolizer>
                </Rule>
            </FeatureTypeStyle>
        </UserStyle>
    </NamedLayer>
</StyledLayerDescriptor>
SLD;
    }
} 