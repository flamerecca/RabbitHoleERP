<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentStatus;
use App\Models\UnbuildOrder;
use Illuminate\Support\Facades\DB;

class CancelUnbuildOrder
{
    /**
     * Cancel a draft unbuild order.
     */
    public function handle(UnbuildOrder $order): UnbuildOrder
    {
        return DB::transaction(function () use ($order) {
            $order = UnbuildOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== DocumentStatus::Draft, 409, __('Only draft unbuild orders can be cancelled.'));

            $order->update(['status' => DocumentStatus::Cancelled]);

            return $order->fresh('items');
        });
    }
}
