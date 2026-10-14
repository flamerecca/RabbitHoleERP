<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\StockTake;
use Illuminate\Support\Facades\DB;

class CancelStockTake
{
    /**
     * Cancel a draft stock take.
     */
    public function handle(StockTake $document): StockTake
    {
        return DB::transaction(function () use ($document) {
            $document = StockTake::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft stock takes can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
