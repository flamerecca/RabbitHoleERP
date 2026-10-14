<?php

namespace App\Actions\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class CancelPurchaseOrder
{
    /**
     * Cancel a draft or confirmed purchase order that has not received any goods.
     */
    public function handle(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_unless(
                in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Confirmed], true)
                    && $order->items()->where('received_quantity', '>', 0)->doesntExist(),
                409,
                __('Purchase orders with received goods cannot be cancelled.'),
            );

            $order->update(['status' => PurchaseOrderStatus::Cancelled]);

            return $order->fresh('items');
        });
    }
}
