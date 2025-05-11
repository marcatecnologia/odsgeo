<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Servico>
 */
class ServicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'projeto_id' será preenchido pelo relacionamento has() no seeder
            'nome' => $this->faker->word,
            'tipo' => $this->faker->randomElement(['ods', 'memorial', 'outro']),
            'descricao' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['pendente', 'em_andamento', 'concluido', 'cancelado']),
            'data_inicio' => $this->faker->date(),
            'data_fim' => $this->faker->optional()->date(),
            // 'diretorio' é preenchido automaticamente pelo model
        ];
    }
}
