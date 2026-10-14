<?php

namespace App\Filament\Resources\Boms\Pages;

use App\Actions\Mrp\SaveBom;
use App\Filament\Resources\Boms\BomResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBoms extends ManageRecords
{
    protected static string $resource = BomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SaveBom::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
