<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Actions\MasterData\SaveCustomer;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SaveCustomer::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
