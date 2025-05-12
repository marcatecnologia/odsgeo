<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Criar papéis
        $roles = [
            'admin' => 'Administrador do Sistema',
            'visualizador_sigef' => 'Visualizador SIGEF',
        ];

        foreach ($roles as $name => $description) {
            Role::findOrCreate($name, 'web');
        }

        // Criar permissões
        $permissions = [
            'view_sigef' => 'Visualizar Parcelas SIGEF',
            'manage_sigef' => 'Gerenciar Parcelas SIGEF',
        ];

        foreach ($permissions as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        // Atribuir permissões aos papéis
        $adminRole = Role::findByName('admin', 'web');
        $visualizadorRole = Role::findByName('visualizador_sigef', 'web');

        $adminRole->givePermissionTo($permissions);
        $visualizadorRole->givePermissionTo(['view_sigef']);
    }
} 