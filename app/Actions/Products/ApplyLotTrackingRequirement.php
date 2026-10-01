<?php

namespace App\Actions\Products;

use App\Enums\ProductTracking;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class ApplyLotTrackingRequirement
{
    /**
     * Switch the untracked products of the team without stock movements to lot tracking when their rule requires it.
     */
    public function forTeam(Team $team): void
    {
        $this->apply(Product::query()->where('team_id', $team->id));
    }

    /**
     * Switch the untracked products of the category without stock movements to lot tracking when their rule requires it.
     */
    public function forCategory(ProductCategory $category): void
    {
        $this->apply(Product::query()->where('category_id', $category->id));
    }

    /**
     * Switch the matching products, leaving products with stock movements as they are.
     *
     * @param  Builder<Product>  $products
     */
    protected function apply(Builder $products): void
    {
        $products
            ->where('tracking', ProductTracking::None)
            ->whereNotIn('id', StockMovement::query()->select('product_id'))
            ->get()
            ->filter(fn (Product $product) => $product->requiresLotTracking())
            ->each(fn (Product $product) => $product->update(['tracking' => ProductTracking::Lot]));
    }
}
