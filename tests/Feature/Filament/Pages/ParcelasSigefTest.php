<?php

namespace Tests\Feature\Filament\Pages;

use Tests\TestCase;
use App\Services\SigefWfsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleSeeder;

class ParcelasSigefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Garantir que os papéis existam
        $this->seed(RoleSeeder::class);

        // Criar usuário com permissão
        $this->user = User::factory()->create();
        $this->user->assignRole('visualizador_sigef');
    }

    public function test_papel_visualizador_sigef_existe()
    {
        $this->assertTrue(Role::where('name', 'visualizador_sigef')->exists());
    }

    public function test_pagina_requer_autenticacao()
    {
        $response = $this->get(route('filament.admin.pages.parcelas-sigef'));
        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_pagina_requer_permissao()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.pages.parcelas-sigef'));
        $response->assertForbidden();
    }

    public function test_pagina_carrega_com_sucesso()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('filament.admin.pages.parcelas-sigef'));
        $response->assertSuccessful();
    }

    public function test_carrega_municipios_ao_selecionar_estado()
    {
        $this->actingAs($this->user);

        // Mock do serviço WFS
        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('municipios', ['CQL_FILTER' => "uf = 'SP'"])
            ->andReturn([
                'success' => true,
                'data' => json_encode([
                    'features' => [
                        [
                            'properties' => [
                                'codigo' => '3550308',
                                'nome' => 'São Paulo'
                            ]
                        ]
                    ]
                ])
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $response = $this->post(route('filament.admin.pages.parcelas-sigef.load-municipios'), [
            'estado' => 'SP'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            '3550308' => 'São Paulo'
        ]);
    }

    public function test_busca_parcelas_ao_selecionar_municipio()
    {
        $this->actingAs($this->user);

        // Mock do serviço WFS
        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('parcelas', [
                'CQL_FILTER' => "uf = 'SP' AND municipio = '3550308'",
                'maxFeatures' => 100
            ])
            ->andReturn([
                'success' => true,
                'data' => json_encode([
                    'features' => [
                        [
                            'properties' => [
                                'numero_parcela' => '123',
                                'area_ha' => 100.5,
                                'situacao' => 'Regular',
                                'tipo' => 'Rural'
                            ]
                        ]
                    ]
                ])
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $response = $this->post(route('filament.admin.pages.parcelas-sigef.buscar-parcelas'), [
            'estado' => 'SP',
            'municipio' => '3550308'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'features' => [
                [
                    'properties' => [
                        'numero_parcela' => '123',
                        'area_ha' => 100.5,
                        'situacao' => 'Regular',
                        'tipo' => 'Rural'
                    ]
                ]
            ]
        ]);
    }

    public function test_retorna_erro_quando_servico_wfs_falha()
    {
        $this->actingAs($this->user);

        // Mock do serviço WFS com erro
        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Serviço temporariamente indisponível'
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $response = $this->post(route('filament.admin.pages.parcelas-sigef.buscar-parcelas'), [
            'estado' => 'SP',
            'municipio' => '3550308'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'error' => 'Serviço temporariamente indisponível'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 