<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Painel de Controle';
    protected static ?string $title = 'Painel de Controle';

    public function render(): View
    {
        return view('filament.pages.dashboard');
    }
}
