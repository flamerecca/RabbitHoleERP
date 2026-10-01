<?php

namespace App\Actions\Inventory;

use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;

class UnreserveSalesOrderStock
{
    /**
     * Release the whole stock reservation of the sales order.
     */
    public function handle(SalesOrder $salesOrder): void
    {
        DB::transaction(function () use ($salesOrder) {
            $items = $salesOrder->items()->where('reserved_quantity', '>', 0)->orderBy('id')->get();

            foreach ($items as $item) {
                $this->release($salesOrder->warehouse_id, $item, (float) $item->reserved_quantity);
            }
        });
    }

    /**
     * Release part of the reservation of one line from the warehouse balance.
     */
    public function release(int $warehouseId, SalesOrderItem $item, float $quantity): void
    {
        $quantity = min(round($quantity, 4), (float) $item->reserved_quantity);

        if ($quantity <= 0) {
            return;
        }

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $item->product_id)
            ->lockForUpdate()
            ->first();

        $balance?->update(['quantity_reserved' => max(0.0, round((float) $balance->quantity_reserved - $quantity, 4))]);
        $item->update(['reserved_quantity' => round((float) $item->reserved_quantity - $quantity, 4)]);
    }

    /**
     * Release reservations, latest sales order first, until the reserved quantity no longer exceeds the stock on hand.
     */
    public function releaseExcess(int $warehouseId, int $productId): void
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($balance === null) {
            return;
        }

        $excess = round((float) $balance->quantity_reserved - (float) $balance->quantity_on_hand, 4);

        $items = SalesOrderItem::query()
            ->select('sales_order_items.*')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.warehouse_id', $warehouseId)
            ->whereIn('sales_orders.status', [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped])
            ->where('sales_order_items.product_id', $productId)
            ->where('sales_order_items.reserved_quantity', '>', 0)
            ->orderByDesc('sales_orders.id')
            ->orderByDesc('sales_order_items.id')
            ->get();

        foreach ($items as $item) {
            if ($excess <= 0) {
                return;
            }

            $released = min($excess, (float) $item->reserved_quantity);
            $this->release($warehouseId, $item, $released);
            $excess = round($excess - $released, 4);
        }
    }
}
