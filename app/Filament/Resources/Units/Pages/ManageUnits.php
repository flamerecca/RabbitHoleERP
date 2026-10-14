<?php

namespace App\Filament\Resources\Units\Pages;

use App\Actions\Products\CreateUnit;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Support\DomainAction;
use App\Models\Team;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageUnits extends ManageRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, CreateAction $action) {
                    /** @var Team $team */
                    $team = Filament::getTenant();

                    return DomainAction::run(fn () => app(CreateUnit::class)->handle($team, $data), fn () => $action->halt());
                }),
        ];
    }
}
