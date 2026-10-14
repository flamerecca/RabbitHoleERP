<?php

namespace App\Actions\Sales;

use App\Enums\DocumentStatus;
use App\Models\SalesReturn;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSalesReturn
{
    /**
     * Update the header and optionally replace the lines of a draft sales return.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(SalesReturn $document, array $attributes): SalesReturn
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = SalesReturn::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft sales returns can be updated.'));

            $document->update(Arr::only($attributes, ['warehouse_id', 'return_date', 'reason']));

            if (array_key_exists('items', $attributes)) {
                CreateSalesReturn::replaceLines($document, $attributes['items']);
            } else {
                CreateSalesReturn::refreshWholeLots($document);
            }

            return $document->fresh('items');
        });
    }
}
