<?php

namespace App\Actions\Sales;

use App\Enums\DocumentStatus;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class CancelShipment
{
    /**
     * Cancel a draft shipment.
     */
    public function handle(Shipment $document): Shipment
    {
        return DB::transaction(function () use ($document) {
            $document = Shipment::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft shipments can be cancelled.'));

            $document->update(['status' => DocumentStatus::Cancelled]);

            return $document->fresh('items');
        });
    }
}
