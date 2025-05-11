<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DadosFicticiosSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Cliente::factory(5)
            ->has(
                \App\Models\Projeto::factory(2)
                    ->has(
                        \App\Models\Servico::factory(3)
                    )
            )
            ->create();
    }
} 