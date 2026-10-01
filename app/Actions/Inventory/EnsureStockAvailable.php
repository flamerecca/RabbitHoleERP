<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Support\Collection;

class EnsureStockAvailable
{
    /**
     * Lock the warehouse balances and abort with a conflict when a product has less unreserved stock than required.
     *
     * @param  array<int, float>  $requiredByProduct  Quantities in the stock unit, keyed by product id.
     * @param  Collection<int, Product>  $products  The products of the document, keyed by id, for the error message.
     */
    public function handle(int $warehouseId, array $requiredByProduct, Collection $products): void
    {
        foreach ($requiredByProduct as $productId => $required) {
            $balance = StockBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();
            $available = $balance === null ? 0.0 : (float) $balance->quantity_on_hand - (float) $balance->quantity_reserved;

            abort_if(
                round($required, 4) > round($available, 4),
                409,
                __('Insufficient stock for product [:sku].', ['sku' => $products->get($productId)?->sku]),
            );
        }
    }
}
