<?php

namespace App\Filament\Resources\ConsignmentHolds\Pages;

use App\Filament\Resources\ConsignmentHolds\ConsignmentHoldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsignmentHolds extends ListRecords
{
    protected static string $resource = ConsignmentHoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
