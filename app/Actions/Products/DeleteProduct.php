<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProduct
{
    /**
     * Tables whose rows keep a product from being deleted.
     *
     * @var list<string>
     */
    protected const REFERENCING_TABLES = [
        'stock_balances',
        'stock_movements',
        'purchase_order_items',
        'goods_receipt_items',
        'purchase_return_items',
        'sales_order_items',
        'shipment_items',
        'sales_return_items',
        'warehouse_transfer_items',
        'stock_take_items',
        'material_requisition_items',
    ];

    /**
     * Delete a product without stock or document lines, together with its supplier prices and reordering rules.
     */
    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product) {
            foreach (self::REFERENCING_TABLES as $table) {
                abort_if(
                    DB::table($table)->where('product_id', $product->id)->exists(),
                    409,
                    __('Products with stock or document lines cannot be deleted. Deactivate the product instead.'),
                );
            }

            $product->productSuppliers()->get()->each->delete();
            $product->reorderingRules()->get()->each->delete();
            $product->delete();
        });
    }
}
