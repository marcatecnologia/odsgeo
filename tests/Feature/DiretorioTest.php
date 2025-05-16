<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\Servico;
use App\Services\CacheService;
use App\Services\DiretorioService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiretorioTest extends TestCase
{
    use RefreshDatabase;

    protected $cacheService;
    protected $diretorioService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(CacheService::class);
        $this->diretorioService = app(DiretorioService::class);
        
        Storage::fake('local');
    }

    public function test_selecao_diretorio()
    {
        $cliente = Cliente::factory()->create();
        $projeto = Projeto::factory()->create(['cliente_id' => $cliente->id]);
        $servico = Servico::factory()->create(['projeto_id' => $projeto->id]);

        $response = $this->get(route('selecionar.diretorio'));

        $response->assertStatus(200)
            ->assertSee($cliente->nome)
            ->assertSee($projeto->nome)
            ->assertSee($servico->nome);
    }

    public function test_cache_servico_completo()
    {
        $cliente = Cliente::factory()->create();
        $projeto = Projeto::factory()->create(['cliente_id' => $cliente->id]);
        $servico = Servico::factory()->create(['projeto_id' => $projeto->id]);

        $servicoCompleto = $this->cacheService->getServicoCompleto($servico->id);

        $this->assertNotNull($servicoCompleto);
        $this->assertEquals($servico->id, $servicoCompleto->id);
        $this->assertEquals($projeto->id, $servicoCompleto->projeto_id);
        $this->assertEquals($cliente->id, $servicoCompleto->projeto->cliente_id);
    }

    public function test_criacao_estrutura_diretorios()
    {
        $diretorioBase = 'test/servico';
        
        $this->diretorioService->criarEstruturaDiretoriosServico($diretorioBase);

        foreach ($this->diretorioService->estruturaBase as $categoria => $subcategorias) {
            $this->assertTrue(Storage::exists($diretorioBase . '/' . $categoria));
        }
    }

    public function test_listagem_arquivos_por_categoria()
    {
        $diretorioBase = 'test/servico';
        $categoria = 'planilhas';
        
        Storage::put($diretorioBase . '/' . $categoria . '/teste.xlsx', 'conteudo');
        
        $arquivos = $this->diretorioService->listarArquivosPorCategoria($diretorioBase, $categoria);
        
        $this->assertCount(1, $arquivos);
        $this->assertStringContainsString('teste.xlsx', $arquivos[0]);
    }

    public function test_movimentacao_arquivo()
    {
        $diretorioBase = 'test/servico';
        $categoriaOrigem = 'documentos';
        $categoriaDestino = 'planilhas';
        $arquivo = 'teste.xlsx';
        
        Storage::put($diretorioBase . '/' . $categoriaOrigem . '/' . $arquivo, 'conteudo');
        
        $novoLocal = $this->diretorioService->moverArquivo(
            $diretorioBase . '/' . $categoriaOrigem . '/' . $arquivo,
            $categoriaDestino,
            $diretorioBase
        );
        
        $this->assertTrue(Storage::exists($novoLocal));
        $this->assertFalse(Storage::exists($diretorioBase . '/' . $categoriaOrigem . '/' . $arquivo));
    }

    public function test_remocao_diretorio()
    {
        $diretorio = 'test/servico';
        
        Storage::makeDirectory($diretorio);
        Storage::put($diretorio . '/teste.txt', 'conteudo');
        
        $this->diretorioService->removerDiretorio($diretorio);
        
        $this->assertFalse(Storage::exists($diretorio));
    }
} 