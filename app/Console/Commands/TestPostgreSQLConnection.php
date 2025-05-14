<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestPostgreSQLConnection extends Command
{
    protected $signature = 'postgresql:test-connection';
    protected $description = 'Testa a conexão com o banco de dados PostgreSQL';

    public function handle()
    {
        $this->info('Testando conexão com o PostgreSQL...');

        try {
            // 1. Testar conexão básica
            $this->testBasicConnection();

            // 2. Testar extensão PostGIS
            $this->testPostGIS();

            // 3. Testar tabela de municípios
            $this->testMunicipiosTable();

            $this->info('Testes concluídos com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro durante os testes: ' . $e->getMessage());
            Log::error('Erro nos testes do PostgreSQL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function testBasicConnection()
    {
        $this->info('Testando conexão básica...');

        try {
            DB::connection()->getPdo();
            $this->info('Conexão bem sucedida!');
        } catch (\Exception $e) {
            throw new \Exception('Erro ao conectar com o PostgreSQL: ' . $e->getMessage());
        }
    }

    protected function testPostGIS()
    {
        $this->info('Testando extensão PostGIS...');

        try {
            $version = DB::select('SELECT PostGIS_version() as version')[0]->version;
            $this->info("PostGIS instalado! Versão: {$version}");
        } catch (\Exception $e) {
            throw new \Exception('Erro ao testar PostGIS: ' . $e->getMessage());
        }
    }

    protected function testMunicipiosTable()
    {
        $this->info('Testando tabela de municípios...');

        try {
            $count = DB::table('municipios_simplificado')->count();
            $this->info("Tabela de municípios encontrada! Total de registros: {$count}");

            // Verificar se a coluna geom existe
            $columns = DB::getSchemaBuilder()->getColumnListing('municipios_simplificado');
            if (!in_array('geom', $columns)) {
                throw new \Exception('Coluna geom não encontrada na tabela municipios_simplificado');
            }
            $this->info('Coluna geom encontrada!');
        } catch (\Exception $e) {
            throw new \Exception('Erro ao testar tabela de municípios: ' . $e->getMessage());
        }
    }
} 