<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateWarehouseTransfer
{
    /**
     * Update the date and optionally replace the lines of a draft transfer; the warehouses never change.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(WarehouseTransfer $document, array $attributes): WarehouseTransfer
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = WarehouseTransfer::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft warehouse transfers can be updated.'));

            $document->update(Arr::only($attributes, ['transfer_date']));

            if (array_key_exists('items', $attributes)) {
                $document->items()->get()->each->delete();
                $document->items()->createMany($attributes['items']);
            }

            return $document->fresh('items');
        });
    }
}
