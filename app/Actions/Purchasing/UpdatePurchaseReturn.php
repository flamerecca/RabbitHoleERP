<?php

namespace App\Actions\Purchasing;

use App\Enums\DocumentStatus;
use App\Models\PurchaseReturn;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseReturn
{
    /**
     * Update the header and optionally replace the lines of a draft purchase return.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(PurchaseReturn $document, array $attributes): PurchaseReturn
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = PurchaseReturn::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft purchase returns can be updated.'));

            $document->update(Arr::only($attributes, ['warehouse_id', 'return_date', 'reason']));

            if (array_key_exists('items', $attributes)) {
                CreatePurchaseReturn::replaceLines($document, $attributes['items']);
            } else {
                CreatePurchaseReturn::refreshWholeLots($document);
            }

            return $document->fresh('items');
        });
    }
}
