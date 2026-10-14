<?php

namespace App\Filament\Resources\MaterialRequisitions\Pages;

use App\Filament\Resources\MaterialRequisitions\MaterialRequisitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialRequisitions extends ListRecords
{
    protected static string $resource = MaterialRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
