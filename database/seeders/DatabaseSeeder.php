<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar papéis e permissões primeiro
        $this->call([
            RoleSeeder::class,
            ProjetoSeeder::class,
            ServicoSeeder::class,
            DadosFicticiosSeeder::class,
        ]);

        // Criar ou atualizar usuário de teste com papel de admin
        $user = User::firstOrCreate(
            ['email' => 'marcos@grupomarca.org'],
            [
                'name' => 'Marcos',
                'password' => Hash::make('33121201'),
                'email_verified_at' => now(),
            ]
        );

        // Garantir que o usuário tenha o papel de admin
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }
}
