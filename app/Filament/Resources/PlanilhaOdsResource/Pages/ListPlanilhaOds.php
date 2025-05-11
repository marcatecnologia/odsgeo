<?php

namespace App\Filament\Resources\PlanilhaOdsResource\Pages;

use App\Filament\Resources\PlanilhaOdsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanilhaOds extends ListRecords
{
    protected static string $resource = PlanilhaOdsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 