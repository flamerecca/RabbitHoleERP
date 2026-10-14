<?php

namespace App\Actions\Mrp;

use App\Enums\ManufacturingOrderStatus;
use App\Models\ManufacturingOrder;
use Illuminate\Support\Facades\DB;

class CancelManufacturingOrder
{
    /**
     * Cancel a draft or confirmed manufacturing order, which has not touched the stock yet.
     */
    public function handle(ManufacturingOrder $order): ManufacturingOrder
    {
        return DB::transaction(function () use ($order) {
            $order = ManufacturingOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_unless(
                in_array($order->status, [ManufacturingOrderStatus::Draft, ManufacturingOrderStatus::Confirmed], true),
                409,
                __('Only draft or confirmed manufacturing orders can be cancelled.'),
            );

            $order->update(['status' => ManufacturingOrderStatus::Cancelled]);

            return $order->fresh('items');
        });
    }
}
