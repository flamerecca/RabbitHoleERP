<?php

namespace App\Filament\Resources\PosConfigs\Pages;

use App\Actions\Pos\SavePosConfig;
use App\Filament\Resources\PosConfigs\PosConfigResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePosConfigs extends ManageRecords
{
    protected static string $resource = PosConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => PosConfigResource::canCreate())
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SavePosConfig::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
