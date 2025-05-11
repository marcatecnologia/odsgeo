<?php

namespace App\Filament\Resources\CoordinateResource\Pages;

use App\Filament\Resources\CoordinateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListarCoordenadas extends ListRecords
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
            Action::make('import')
                ->label('Importar Coordenadas')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalContent(view('livewire.importar-coordenadas')),
        ];
    }
} 