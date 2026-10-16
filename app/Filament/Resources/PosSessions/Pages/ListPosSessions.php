<?php

namespace App\Filament\Resources\PosSessions\Pages;

use App\Filament\Resources\PosSessions\PosSessionActions;
use App\Filament\Resources\PosSessions\PosSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListPosSessions extends ListRecords
{
    protected static string $resource = PosSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PosSessionActions::open(),
        ];
    }
}
