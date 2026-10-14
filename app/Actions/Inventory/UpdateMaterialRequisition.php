<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\MaterialRequisition;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateMaterialRequisition
{
    /**
     * Update the header and optionally replace the lines of a draft material requisition; the warehouse never changes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(MaterialRequisition $document, array $attributes): MaterialRequisition
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = MaterialRequisition::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft material requisitions can be updated.'));

            $document->update(Arr::only($attributes, ['requisition_date', 'purpose', 'requested_by']));

            if (array_key_exists('items', $attributes)) {
                $document->items()->get()->each->delete();
                $document->items()->createMany($attributes['items']);
            }

            return $document->fresh('items');
        });
    }
}
