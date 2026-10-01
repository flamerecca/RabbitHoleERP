<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Actions\MasterData\SaveCurrency;
use App\Filament\Resources\Currencies\CurrencyResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCurrencies extends ManageRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SaveCurrency::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
