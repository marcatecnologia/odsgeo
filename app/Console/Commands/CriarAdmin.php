<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CriarAdmin extends Command
{
    protected $signature = 'admin:criar {email} {senha}';
    protected $description = 'Cria um usuário administrador';

    public function handle()
    {
        $email = $this->argument('email');
        $senha = $this->argument('senha');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => Hash::make($senha),
            ]
        );

        $this->info('Usuário administrador ' . ($user->wasRecentlyCreated ? 'criado' : 'atualizado') . ' com sucesso!');
        $this->info('Email: ' . $email);
    }
} 