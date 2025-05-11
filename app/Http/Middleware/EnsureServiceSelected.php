<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir acesso ao login e logout do Filament e ao painel inicial
        $allowedRoutes = [
            'filament.admin.auth.login',
            'filament.admin.auth.logout',
            'filament.admin.pages.painel-inicial',
        ];

        // Permitir acesso a todas as rotas de clientes, projetos e serviços
        $routeName = $request->route()->getName();
        if (
            str_starts_with($routeName, 'filament.admin.resources.clientes') ||
            str_starts_with($routeName, 'filament.admin.resources.projetos') ||
            str_starts_with($routeName, 'filament.admin.resources.servicos')
        ) {
            return $next($request);
        }

        if (!session()->has('current_service_id')) {
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('filament.admin.pages.painel-inicial');
            }
        }

        return $next($request);
    }
} 