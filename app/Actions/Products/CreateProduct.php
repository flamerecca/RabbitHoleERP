<?php

namespace App\Actions\Products;

use App\Enums\ProductTracking;
use App\Models\Product;
use App\Models\TaxRate;
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
        static::ensureTaxRateAndBarcode($team->id, $attributes, null);

        if (array_key_exists('barcode', $attributes)) {
            $attributes['barcode'] = ($attributes['barcode'] ?? null) ?: null;
        }

        $attributes['purchase_unit_id'] ??= $attributes['unit_id'];
        $attributes = static::withRequiredLotTracking($team->id, $attributes['category_id'] ?? null, $attributes);

        static::ensureUnitsShareCategory((int) $attributes['unit_id'], (int) $attributes['purchase_unit_id']);

        $tracking = $attributes['tracking'] ?? ProductTracking::None;

        app(EnsureRefurbishedProduct::class)->handle(
            $team->id,
            null,
            isset($attributes['refurbished_product_id']) ? (int) $attributes['refurbished_product_id'] : null,
            (int) $attributes['unit_id'],
            $tracking instanceof ProductTracking ? $tracking : ProductTracking::from((string) $tracking),
        );

        if (($tracking instanceof ProductTracking ? $tracking : ProductTracking::from((string) $tracking)) === ProductTracking::Lot) {
            $attributes['expiry_date'] = null;
        }

        return Product::create([
            'is_active' => true,
            'tracking' => ProductTracking::None,
            ...$attributes,
            'team_id' => $team->id,
        ]);
    }

    /**
     * Fail validation when the tax rate belongs to another team or another product of the team has the barcode.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public static function ensureTaxRateAndBarcode(int $teamId, array $attributes, ?int $ignoreProductId): void
    {
        if (($attributes['tax_rate_id'] ?? null) !== null
            && ! TaxRate::query()->where('team_id', $teamId)->whereKey($attributes['tax_rate_id'])->exists()) {
            throw ValidationException::withMessages(['tax_rate_id' => __('The tax rate must belong to the team.')]);
        }

        $barcode = $attributes['barcode'] ?? null;

        if ($barcode !== null && $barcode !== ''
            && Product::query()
                ->where('team_id', $teamId)
                ->where('barcode', $barcode)
                ->when($ignoreProductId !== null, fn ($query) => $query->whereKeyNot($ignoreProductId))
                ->exists()) {
            throw ValidationException::withMessages(['barcode' => __('The barcode is already used by another product.')]);
        }
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
