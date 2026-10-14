<?php

namespace App\Filament\Resources\ManufacturingOrders\Pages;

use App\Filament\Resources\ManufacturingOrders\ManufacturingOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManufacturingOrders extends ListRecords
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
