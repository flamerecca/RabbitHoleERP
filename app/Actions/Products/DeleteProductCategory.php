<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductCategory;

class DeleteProductCategory
{
    /**
     * Delete a product category without subcategories or products.
     */
    public function handle(ProductCategory $category): void
    {
        abort_if(
            ProductCategory::query()->where('parent_id', $category->id)->exists() || Product::query()->where('category_id', $category->id)->exists(),
            409,
            __('Categories with subcategories or products cannot be deleted.'),
        );

        $category->delete();
    }
}
