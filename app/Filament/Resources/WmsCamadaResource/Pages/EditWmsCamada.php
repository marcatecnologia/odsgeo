<?php

namespace App\Filament\Resources\WmsCamadaResource\Pages;

use App\Filament\Resources\WmsCamadaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWmsCamada extends EditRecord
{
    protected static string $resource = WmsCamadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 