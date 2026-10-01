<?php

namespace App\Actions\Products;

use App\Enums\CostMethod;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProductCategory
{
    /**
     * Update a product category, refusing parent cycles and cost method changes once its products have stock movements,
     * and switching its products to lot tracking when the category now requires it.
     * The rules compare against a freshly locked copy, because callers such as Filament may already have filled the given model.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(ProductCategory $category, array $attributes): ProductCategory
    {
        return DB::transaction(function () use ($category, $attributes) {
            $category = ProductCategory::query()->whereKey($category->getKey())->lockForUpdate()->firstOrFail();
            if (! empty($attributes['parent_id']) && $this->wouldCreateCycle($category, (int) $attributes['parent_id'])) {
                throw ValidationException::withMessages(['parent_id' => __('A category cannot be placed under itself or one of its subcategories.')]);
            }

            $costMethod = isset($attributes['cost_method']) ? CostMethod::from($attributes['cost_method'] instanceof CostMethod ? $attributes['cost_method']->value : (string) $attributes['cost_method']) : null;

            abort_if(
                $costMethod !== null && $costMethod !== $category->cost_method && $this->hasStockMovements($category),
                409,
                __('The cost method cannot change after the products of the category have stock movements.'),
            );

            $category->update($attributes);

            app(ApplyLotTrackingRequirement::class)->forCategory($category);

            return $category;
        });
    }

    /**
     * Determine if the parent is the category itself or one of its descendants.
     */
    protected function wouldCreateCycle(ProductCategory $category, int $parentId): bool
    {
        $visited = [];

        for ($current = $parentId; $current !== null && ! in_array($current, $visited, true); $current = ProductCategory::query()->whereKey($current)->value('parent_id')) {
            if ($current === $category->id) {
                return true;
            }

            $visited[] = $current;
        }

        return false;
    }

    /**
     * Determine if any product of the category has stock movements.
     */
    protected function hasStockMovements(ProductCategory $category): bool
    {
        return DB::table('stock_movements')
            ->whereIn('product_id', DB::table('products')->where('category_id', $category->id)->select('id'))
            ->exists();
    }
}
