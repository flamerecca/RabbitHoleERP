<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\StockLotBalance;

class AllocateStockLots
{
    /**
     * Decide which lots an issue of the product consumes: only the given lot, or the oldest lots first.
     *
     * Products not tracked by lot get a single allocation without a lot. The lot balances are locked
     * until the end of the surrounding transaction.
     *
     * @return list<array{stock_lot_id: int|null, quantity: float}>
     */
    public function handle(int $warehouseId, Product $product, float $quantity, ?int $stockLotId = null): array
    {
        if (! $product->isLotTracked()) {
            return [['stock_lot_id' => null, 'quantity' => $quantity]];
        }

        $balances = StockLotBalance::query()
            ->with('stockLot')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $product->id)
            ->where('quantity_on_hand', '>', 0)
            ->when($stockLotId !== null, fn ($query) => $query->where('stock_lot_id', $stockLotId))
            ->orderBy('stock_lot_id')
            ->lockForUpdate()
            ->get();

        $allocations = [];
        $remaining = round($quantity, 4);

        foreach ($balances as $balance) {
            if ($remaining <= 0) {
                break;
            }

            $taken = min($remaining, (float) $balance->quantity_on_hand);
            $allocations[] = ['stock_lot_id' => $balance->stock_lot_id, 'quantity' => $taken];
            $remaining = round($remaining - $taken, 4);
        }

        abort_if(
            $remaining > 0,
            409,
            $stockLotId === null
                ? __('Insufficient stock for product [:sku].', ['sku' => $product->sku])
                : __('Insufficient stock in lot [:lot].', ['lot' => $balances->first()?->stockLot->lot_no ?? $stockLotId]),
        );

        return $allocations;
    }
}
