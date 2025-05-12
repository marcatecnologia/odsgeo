<?php

namespace App\Filament\Resources\WmsCamadaResource\Pages;

use App\Filament\Resources\WmsCamadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWmsCamadas extends ListRecords
{
    protected static string $resource = WmsCamadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 