<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Products\CreateProduct as CreateProductAction;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\DomainAction;
use App\Models\Team;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Create the product through the shared product action.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        return DomainAction::run(fn () => app(CreateProductAction::class)->handle($team, $data), fn () => $this->halt());
    }
}
