<?php

namespace App\Filament\Resources\PlanilhaOdsResource\Pages;

use App\Filament\Resources\PlanilhaOdsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanilhaOds extends EditRecord
{
    protected static string $resource = PlanilhaOdsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('exportar')
                ->label('Exportar ODS')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->record->exportarOds()),
        ];
    }
} 