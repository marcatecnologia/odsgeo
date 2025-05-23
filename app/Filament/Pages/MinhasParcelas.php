<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Services\SigefWfsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MinhasParcelas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Minhas Parcelas';
    protected static ?string $title = 'Minhas Parcelas';
    protected static ?string $navigationGroup = 'Parcelas SIGEF';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.minhas-parcelas';

    public function mount()
    {
        // Implementação futura
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'visualizador_sigef']);
    }
} 