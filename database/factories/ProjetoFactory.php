<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Projeto>
 */
class ProjetoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->catchPhrase,
            'descricao' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['ativo', 'concluido', 'cancelado']),
            'data_inicio' => $this->faker->date(),
            'data_fim' => $this->faker->optional()->date(),
        ];
    }
}
