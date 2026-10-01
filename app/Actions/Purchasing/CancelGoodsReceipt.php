<?php

namespace App\Actions\Purchasing;

use App\Enums\DocumentStatus;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\DB;

class CancelGoodsReceipt
{
    /**
     * Cancel a draft goods receipt.
     */
    public function handle(GoodsReceipt $document): GoodsReceipt
    {
        return DB::transaction(function () use ($document) {
            $document = GoodsReceipt::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft goods receipts can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
