<?php

namespace Tests\Feature\Filament\Pages;

use Tests\TestCase;
use App\Services\SigefWfsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;
use Illuminate\Support\Facades\Cache;
use App\Filament\Pages\ParcelasSigef;
use Filament\Notifications\Notification;

class ParcelasSigefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
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
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(ParcelasSigef::class)
            ->assertViewIs('filament.pages.parcelas-sigef');
    }

    public function test_carrega_municipios_ao_selecionar_estado()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

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

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        
        $this->assertEquals('SP', $component->get('data.estado'));
        $this->assertTrue(Cache::has('municipios_SP'));
        $this->assertEquals(['3550308' => 'São Paulo'], Cache::get('municipios_SP'));
    }

    public function test_busca_parcelas_ao_selecionar_municipio()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

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

        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('sigef:parcela', [
                'CQL_FILTER' => "uf='SP' AND municipio='3550308'"
            ])
            ->andReturn([
                'success' => true,
                'data' => json_encode([
                    'type' => 'FeatureCollection',
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

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        $component->set('data.municipio', '3550308');
        
        $this->assertEquals('3550308', $component->get('data.municipio'));
        $this->assertNotNull($component->get('searchResults'));
    }

    public function test_retorna_erro_quando_servico_wfs_falha()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

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

        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('sigef:parcela', [
                'CQL_FILTER' => "uf='SP' AND municipio='3550308'"
            ])
            ->andReturn([
                'success' => false,
                'error' => 'Serviço temporariamente indisponível'
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        $component->set('data.municipio', '3550308');
        
        $this->assertEquals('3550308', $component->get('data.municipio'));
        $this->assertNull($component->get('searchResults'));
    }

    public function test_handles_empty_html_response()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('sigef:parcela', [
                'CQL_FILTER' => "uf='SP' AND municipio='SAO PAULO'"
            ])
            ->andReturn([
                'success' => false,
                'error' => 'O serviço SIGEF retornou uma resposta inválida.'
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        $component->set('data.municipio', 'SAO PAULO');

        $this->assertNull($component->get('searchResults'));
    }

    public function test_handles_invalid_json_response()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('sigef:parcela', [
                'CQL_FILTER' => "uf='SP' AND municipio='SAO PAULO'"
            ])
            ->andReturn([
                'success' => false,
                'error' => 'O serviço SIGEF retornou uma resposta inválida.'
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        $component->set('data.municipio', 'SAO PAULO');

        $this->assertNull($component->get('searchResults'));
    }

    public function test_handles_missing_features_in_response()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('sigef:parcela', [
                'CQL_FILTER' => "uf='SP' AND municipio='SAO PAULO'"
            ])
            ->andReturn([
                'success' => false,
                'error' => 'O serviço SIGEF retornou uma resposta inválida.'
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        $component->set('data.municipio', 'SAO PAULO');

        $this->assertNull($component->get('searchResults'));
    }

    public function test_handles_empty_features_array()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'visualizador_sigef')->first();
        $user->assignRole($role);

        $this->actingAs($user);

        $wfsService = Mockery::mock(SigefWfsService::class);
        $wfsService->shouldReceive('getFeature')
            ->once()
            ->with('sigef:parcela', [
                'CQL_FILTER' => "uf='SP' AND municipio='SAO PAULO'"
            ])
            ->andReturn([
                'success' => true,
                'data' => json_encode([
                    'type' => 'FeatureCollection',
                    'features' => []
                ])
            ]);

        $this->app->instance(SigefWfsService::class, $wfsService);

        $component = Livewire::actingAs($user)
            ->test(ParcelasSigef::class);

        $component->set('data.estado', 'SP');
        $component->set('data.municipio', 'SAO PAULO');

        $this->assertNull($component->get('searchResults'));
    }
} 