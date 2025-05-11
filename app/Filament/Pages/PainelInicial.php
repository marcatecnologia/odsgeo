<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PainelInicial extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static string $view = 'filament.pages.painel-inicial';
    protected static ?string $navigationLabel = 'Diretório';
    protected static ?string $title = 'Diretório';
}