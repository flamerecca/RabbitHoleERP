<?php

namespace App\Filament\Resources\UnbuildOrders\Pages;

use App\Filament\Resources\UnbuildOrders\UnbuildOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnbuildOrders extends ListRecords
{
    protected static string $resource = UnbuildOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
