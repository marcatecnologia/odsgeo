<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreateRoles extends Command
{
    protected $signature = 'app:create-roles';
    protected $description = 'Cria os papéis necessários e atribui ao usuário administrador';

    public function handle()
    {
        $this->info('Criando papéis...');

        // Criar papéis
        $roles = [
            'admin' => 'Administrador do sistema',
            'visualizador_sigef' => 'Visualizador do SIGEF',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['name' => $name], [
                'description' => $description,
            ]);
            $this->info("Papel '{$name}' criado/verificado.");
        }

        // Atribuir papel de admin ao usuário
        $user = User::where('email', 'marcos@grupomarca.org')->first();
        if ($user) {
            $user->assignRole('admin');
            $this->info("Papel 'admin' atribuído ao usuário {$user->email}");
        } else {
            $this->error("Usuário não encontrado!");
        }

        $this->info('Processo concluído!');
    }
} 