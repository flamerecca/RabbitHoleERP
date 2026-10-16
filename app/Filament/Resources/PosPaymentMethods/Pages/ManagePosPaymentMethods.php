<?php

namespace App\Filament\Resources\PosPaymentMethods\Pages;

use App\Actions\Pos\SavePosPaymentMethod;
use App\Filament\Resources\PosPaymentMethods\PosPaymentMethodResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePosPaymentMethods extends ManageRecords
{
    protected static string $resource = PosPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => PosPaymentMethodResource::canCreate())
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SavePosPaymentMethod::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
