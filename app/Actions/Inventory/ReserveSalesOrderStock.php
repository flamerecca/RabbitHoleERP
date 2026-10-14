<?php

namespace App\Actions\Inventory;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;

class ReserveSalesOrderStock
{
    /**
     * Reserve available stock in the order warehouse for every line of the sales order, in line order.
     *
     * Each line is topped up to its quantity not yet shipped, converted to the product stock unit. When
     * the warehouse has too little unreserved stock, only what is available is reserved.
     */
    public function handle(SalesOrder $salesOrder): void
    {
        DB::transaction(function () use ($salesOrder) {
            $items = $salesOrder->items()->with(['unit', 'product.unit'])->orderBy('id')->get();

            foreach ($items as $item) {
                $this->reserveLine($salesOrder, $item);
            }
        });
    }

    /**
     * Reserve the missing quantity of one line from the warehouse balance.
     */
    protected function reserveLine(SalesOrder $salesOrder, SalesOrderItem $item): void
    {
        $open = $item->unit->convertQuantity(max(0.0, (float) $item->quantity - (float) $item->shipped_quantity), $item->product->unit);
        $missing = round($open - (float) $item->reserved_quantity, 4);

        if ($missing <= 0) {
            return;
        }

        $balance = StockBalance::query()
            ->where('warehouse_id', $salesOrder->warehouse_id)
            ->where('product_id', $item->product_id)
            ->lockForUpdate()
            ->first();

        if ($balance === null) {
            return;
        }

        $reservable = max(0.0, round((float) $balance->quantity_on_hand - (float) $balance->quantity_reserved, 4));
        $reserved = min($missing, $reservable);

        if ($reserved <= 0) {
            return;
        }

        $balance->update(['quantity_reserved' => round((float) $balance->quantity_reserved + $reserved, 4)]);
        $item->update(['reserved_quantity' => round((float) $item->reserved_quantity + $reserved, 4)]);
    }
}
