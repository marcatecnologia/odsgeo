<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Projeto;
use App\Models\Cliente;

class ProjetoSeeder extends Seeder
{
    public function run(): void
    {
        // Criar cliente padrão
        $cliente = Cliente::create([
            'nome' => 'Cliente Padrão',
            'email' => 'cliente@exemplo.com',
            'telefone' => '(11) 99999-9999',
            'cpf_cnpj' => '12.345.678/0001-90',
            'tipo_pessoa' => 'juridica',
            'observacoes' => 'Cliente padrão para testes',
        ]);

        // Criar projeto padrão
        Projeto::create([
            'nome' => 'Projeto de Georreferenciamento',
            'descricao' => 'Projeto padrão para georreferenciamento de imóveis rurais',
            'status' => 'ativo',
            'cliente_id' => $cliente->id,
        ]);
    }
} 