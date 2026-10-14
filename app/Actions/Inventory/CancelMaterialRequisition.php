<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\MaterialRequisition;
use Illuminate\Support\Facades\DB;

class CancelMaterialRequisition
{
    /**
     * Cancel a draft material requisition.
     */
    public function handle(MaterialRequisition $document): MaterialRequisition
    {
        return DB::transaction(function () use ($document) {
            $document = MaterialRequisition::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft material requisitions can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
