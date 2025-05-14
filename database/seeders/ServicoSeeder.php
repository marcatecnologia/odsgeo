<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Servico;
use App\Models\Projeto;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar o projeto padrão
        $projeto = Projeto::first();

        // Criar serviço padrão
        Servico::create([
            'nome' => 'Serviço de Georreferenciamento',
            'descricao' => 'Serviço padrão de georreferenciamento de imóveis rurais',
            'status' => 'ativo',
            'projeto_id' => $projeto->id,
            'tipo' => 'georreferenciamento',
        ]);
    }
} 