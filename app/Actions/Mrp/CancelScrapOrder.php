<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentStatus;
use App\Models\ScrapOrder;
use Illuminate\Support\Facades\DB;

class CancelScrapOrder
{
    /**
     * Cancel a draft scrap order.
     */
    public function handle(ScrapOrder $order): ScrapOrder
    {
        return DB::transaction(function () use ($order) {
            $order = ScrapOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== DocumentStatus::Draft, 409, __('Only draft scrap orders can be cancelled.'));

            $order->update(['status' => DocumentStatus::Cancelled]);

            return $order->fresh('items');
        });
    }
}
