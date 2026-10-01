<?php

namespace App\Filament\Resources\ProductCategories\Pages;

use App\Actions\Products\CreateProductCategory;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Support\DomainAction;
use App\Models\Team;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageProductCategories extends ManageRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, CreateAction $action) {
                    /** @var Team $team */
                    $team = Filament::getTenant();

                    return DomainAction::run(fn () => app(CreateProductCategory::class)->handle($team, $data), fn () => $action->halt());
                }),
        ];
    }
}
