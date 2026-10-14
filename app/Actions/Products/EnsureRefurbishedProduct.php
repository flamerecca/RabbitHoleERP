<?php

namespace App\Actions\Products;

use App\Enums\ProductTracking;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class EnsureRefurbishedProduct
{
    /**
     * Check the refurbished product of a product against the team, the stock unit and the tracking.
     *
     * The product may be missing while it is being created. A product that is itself the refurbished variant
     * of another product has to keep the stock unit and the tracking of its original.
     *
     * @throws ValidationException
     */
    public function handle(int $teamId, ?Product $product, ?int $refurbishedProductId, int $unitId, ProductTracking $tracking): void
    {
        $errors = [];

        if ($refurbishedProductId !== null) {
            $target = Product::query()->where('team_id', $teamId)->whereKey($refurbishedProductId)->first();

            if ($target === null || ($product !== null && $target->is($product))) {
                $errors['refurbished_product_id'] = __('The selected refurbished product is invalid.');
            } elseif ($target->refurbished_product_id !== null || $product?->refurbishedFrom()->exists()) {
                $errors['refurbished_product_id'] = __('A refurbished product cannot have a refurbished product of its own.');
            } elseif (Product::query()->where('refurbished_product_id', $target->id)->when($product !== null, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
                $errors['refurbished_product_id'] = __('The product is already the refurbished product of another product.');
            } elseif ($target->unit_id !== $unitId || $target->tracking !== $tracking) {
                $errors['refurbished_product_id'] = __('The refurbished product needs the same stock unit and tracking.');
            }
        }

        $original = $product?->refurbishedFrom()->first();

        if ($original !== null && ($original->unit_id !== $unitId || $original->tracking !== $tracking)) {
            $errors['unit_id'] = __('The refurbished product needs the same stock unit and tracking.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
