<?php

namespace App\Filament\Resources\TaxRates\Pages;

use App\Actions\MasterData\SaveTaxRate;
use App\Filament\Resources\TaxRates\TaxRateResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTaxRates extends ManageRecords
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SaveTaxRate::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
