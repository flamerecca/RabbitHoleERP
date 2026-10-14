<?php

namespace App\Actions\Products;

use App\Enums\ProductTracking;
use App\Models\Product;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Validation\ValidationException;

class CreateProduct
{
    /**
     * Create a product whose purchase unit defaults to its stock unit and shares its unit category, tracked by lot
     * when its category or team requires it.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes): Product
    {
        $attributes['purchase_unit_id'] ??= $attributes['unit_id'];
        $attributes = static::withRequiredLotTracking($team->id, $attributes['category_id'] ?? null, $attributes);

        static::ensureUnitsShareCategory((int) $attributes['unit_id'], (int) $attributes['purchase_unit_id']);

        return Product::create([
            'is_active' => true,
            'tracking' => ProductTracking::None,
            ...$attributes,
            'team_id' => $team->id,
        ]);
    }

    /**
     * Fail validation when the purchase unit belongs to another category than the stock unit.
     *
     * @throws ValidationException
     */
    public static function ensureUnitsShareCategory(int $unitId, int $purchaseUnitId): void
    {
        $categories = Unit::query()->whereKey([$unitId, $purchaseUnitId])->pluck('category_id')->unique();

        if ($categories->count() > 1) {
            throw ValidationException::withMessages(['purchase_unit_id' => __('The purchase unit must belong to the same unit category as the stock unit.')]);
        }
    }

    /**
     * Track the product by lot when its category or team requires it, refusing an explicit opt-out.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function withRequiredLotTracking(int $teamId, mixed $categoryId, array $attributes): array
    {
        if (! Product::lotTrackingRequiredFor($teamId, filled($categoryId) ? (int) $categoryId : null)) {
            return $attributes;
        }

        $tracking = $attributes['tracking'] ?? ProductTracking::Lot;

        if (($tracking instanceof ProductTracking ? $tracking : ProductTracking::from((string) $tracking)) !== ProductTracking::Lot) {
            throw ValidationException::withMessages(['tracking' => __('Products of this category must be tracked by lot.')]);
        }

        return [...$attributes, 'tracking' => ProductTracking::Lot];
    }
}
