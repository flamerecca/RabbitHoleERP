<?php

namespace App\Actions\Sales;

use App\Enums\DocumentStatus;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;

class CancelSalesReturn
{
    /**
     * Cancel a draft sales return.
     */
    public function handle(SalesReturn $document): SalesReturn
    {
        return DB::transaction(function () use ($document) {
            $document = SalesReturn::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft sales returns can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
