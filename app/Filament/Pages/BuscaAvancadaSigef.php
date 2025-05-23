<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Navigation\NavigationItem;

class BuscaAvancadaSigef extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Busca Avançada';
    protected static ?string $title = 'Busca Avançada de Parcelas';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.busca-avancada-sigef';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getNavigationIcon())
                ->sort(static::getNavigationSort())
                ->url(static::getUrl()),
        ];
    }
} 