<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;

class CancelWarehouseTransfer
{
    /**
     * Cancel a draft warehouse transfer.
     */
    public function handle(WarehouseTransfer $document): WarehouseTransfer
    {
        return DB::transaction(function () use ($document) {
            $document = WarehouseTransfer::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft warehouse transfers can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
