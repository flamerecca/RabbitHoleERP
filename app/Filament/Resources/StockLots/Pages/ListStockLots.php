<?php

namespace App\Filament\Resources\StockLots\Pages;

use App\Filament\Resources\StockLots\StockLotResource;
use Filament\Resources\Pages\ListRecords;

class ListStockLots extends ListRecords
{
    protected static string $resource = StockLotResource::class;
}
