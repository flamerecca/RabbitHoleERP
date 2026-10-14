<?php

namespace App\Actions\Products;

use App\Enums\CostMethod;
use App\Enums\ProductTracking;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProduct
{
    /**
     * Update a product, refusing stock unit, cost method or tracking changes once it has stock movements, and
     * keeping products without stock movements tracked by lot when their category or team requires it.
     * The rules compare against a freshly locked copy, because callers such as Filament may already have filled the given model.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Product $product, array $attributes): Product
    {
        return DB::transaction(function () use ($product, $attributes) {
            $product = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $hasMovements = DB::table('stock_movements')->where('product_id', $product->id)->exists();

            abort_if(
                $hasMovements && array_key_exists('unit_id', $attributes) && (int) $attributes['unit_id'] !== $product->unit_id,
                409,
                __('The stock unit cannot change after the product has stock movements.'),
            );

            if ($hasMovements && array_key_exists('tracking', $attributes)) {
                abort_if(
                    ProductTracking::from($attributes['tracking'] instanceof ProductTracking ? $attributes['tracking']->value : (string) $attributes['tracking']) !== $product->tracking,
                    409,
                    __('The tracking cannot change after the product has stock movements.'),
                );
            }

            if ($hasMovements && array_key_exists('category_id', $attributes)) {
                $newMethod = $attributes['category_id'] === null
                    ? CostMethod::Average
                    : ProductCategory::query()->whereKey($attributes['category_id'])->firstOrFail()->cost_method;

                abort_if($newMethod !== $product->costMethod(), 409, __('The product cannot move to a category with another cost method after it has stock movements.'));
            }

            if (! $hasMovements) {
                $attributes = CreateProduct::withRequiredLotTracking(
                    $product->team_id,
                    array_key_exists('category_id', $attributes) ? $attributes['category_id'] : $product->category_id,
                    $attributes,
                );
            }

            CreateProduct::ensureUnitsShareCategory(
                (int) ($attributes['unit_id'] ?? $product->unit_id),
                (int) ($attributes['purchase_unit_id'] ?? $product->purchase_unit_id),
            );

            $product->update($attributes);

            return $product;
        });
    }
}
