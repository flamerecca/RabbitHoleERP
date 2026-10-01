<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Products\DeleteProduct;
use App\Actions\Products\UpdateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DomainAction;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DeleteAction::make()
                ->using(fn (Product $record, DeleteAction $action) => DomainAction::run(
                    fn () => app(DeleteProduct::class)->handle($record),
                    fn () => $action->halt(),
                )),
        ];
    }

    /**
     * Update the product through the shared product action.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Product $record */
        return DomainAction::run(fn () => app(UpdateProduct::class)->handle($record, $data), fn () => $this->halt());
    }
}
