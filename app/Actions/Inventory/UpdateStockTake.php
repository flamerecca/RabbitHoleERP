<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\StockTake;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStockTake
{
    /**
     * Update the date and optionally replace the counted lines of a draft stock take.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(StockTake $document, array $attributes): StockTake
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = StockTake::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft stock takes can be updated.'));

            $document->update(Arr::only($attributes, ['taken_date']));

            if (array_key_exists('items', $attributes)) {
                CreateStockTake::replaceLines($document, $attributes['items']);
            }

            return $document->fresh('items');
        });
    }
}
