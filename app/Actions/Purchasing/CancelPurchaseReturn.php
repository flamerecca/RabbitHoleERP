<?php

namespace App\Actions\Purchasing;

use App\Enums\DocumentStatus;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;

class CancelPurchaseReturn
{
    /**
     * Cancel a draft purchase return.
     */
    public function handle(PurchaseReturn $document): PurchaseReturn
    {
        return DB::transaction(function () use ($document) {
            $document = PurchaseReturn::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft purchase returns can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
