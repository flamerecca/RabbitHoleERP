<?php

namespace App\Actions\Mrp;

use App\Actions\Inventory\EnsureStockAvailable;
use App\Enums\ManufacturingOrderStatus;
use App\Models\ManufacturingOrder;
use Illuminate\Support\Facades\DB;

class ConfirmManufacturingOrder
{
    public function __construct(protected EnsureStockAvailable $ensureStockAvailable)
    {
    }

    /**
     * Confirm a draft manufacturing order after checking that the unreserved stock covers its components.
     *
     * Nothing is reserved, the stock is checked again and consumed when the order is completed.
     */
    public function handle(ManufacturingOrder $order): ManufacturingOrder
    {
        return DB::transaction(function () use ($order) {
            $order = ManufacturingOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== ManufacturingOrderStatus::Draft, 409, __('Only draft manufacturing orders can be confirmed.'));

            $order->load('items.component');

            $this->ensureStockAvailable->handle(
                $order->warehouse_id,
                $order->items->mapWithKeys(fn ($line) => [$line->component_product_id => (float) $line->quantity])->all(),
                $order->items->pluck('component', 'component_product_id'),
            );

            $order->update(['status' => ManufacturingOrderStatus::Confirmed]);

            return $order->fresh('items');
        });
    }
}
