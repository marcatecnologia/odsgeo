<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('current_service_id')) {
            // Se não houver serviço selecionado, redireciona para o painel inicial
            if ($request->route()->getName() !== 'filament.admin.pages.painel-inicial') {
                return redirect()->route('filament.admin.pages.painel-inicial');
            }
        }

        return $next($request);
    }
} 