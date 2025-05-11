<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'telefone' => $this->faker->phoneNumber,
            'cpf_cnpj' => $this->faker->unique()->numerify('###########'),
            'tipo_pessoa' => $this->faker->randomElement(['fisica', 'juridica']),
            'observacoes' => $this->faker->sentence,
            // 'diretorio' é preenchido automaticamente pelo model
        ];
    }
}
