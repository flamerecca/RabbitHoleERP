<?php

namespace App\Filament\Resources\ScrapOrders\Pages;

use App\Filament\Resources\ScrapOrders\ScrapOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScrapOrders extends ListRecords
{
    protected static string $resource = ScrapOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
