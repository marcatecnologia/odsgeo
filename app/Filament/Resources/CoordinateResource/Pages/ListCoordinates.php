<?php

namespace App\Filament\Resources\CoordinateResource\Pages;

use App\Filament\Resources\CoordinateResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Livewire;

class ListCoordinates extends ListRecords
{
    protected static string $resource = CoordinateResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('import')
                ->label('Importar Coordenadas')
                ->icon('heroicon-o-arrow-up-tray')
                ->action(function () {
                    Livewire::mount('import-coordinates');
                }),
        ];
    }
} 