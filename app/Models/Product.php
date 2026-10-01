<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\CostMethod;
use App\Enums\LotTrackingPolicy;
use App\Enums\ProductTracking;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 產品主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int|null $category_id
 * @property-read ProductCategory|null $category
 * @property int $unit_id
 * @property-read Unit $unit
 * @property int $purchase_unit_id
 * @property-read Unit $purchaseUnit
 * @property string $sku
 * @property string $name
 * @property string|null $default_purchase_price
 * @property string|null $default_sales_price
 * @property string|null $reorder_point
 * @property bool $is_active
 * @property ProductTracking $tracking
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ProductSupplier> $productSuppliers
 * @property-read Collection<int, ReorderingRule> $reorderingRules
 */
#[Fillable(['team_id', 'category_id', 'unit_id', 'purchase_unit_id', 'sku', 'name', 'default_purchase_price', 'default_sales_price', 'reorder_point', 'is_active', 'tracking'])]
class Product extends Model implements HasActivityLog
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the product.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the category of the product.
     *
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the unit of the product.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the purchase unit of the product.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    /**
     * Get the vendor pricelist entries of the product.
     *
     * @return HasMany<ProductSupplier, $this>
     */
    public function productSuppliers(): HasMany
    {
        return $this->hasMany(ProductSupplier::class);
    }

    /**
     * Get the reordering rules of the product.
     *
     * @return HasMany<ReorderingRule, $this>
     */
    public function reorderingRules(): HasMany
    {
        return $this->hasMany(ReorderingRule::class);
    }

    /**
     * Get the costing method of the product, which comes from its category and defaults to average cost.
     */
    public function costMethod(): CostMethod
    {
        return $this->category === null ? CostMethod::Average : $this->category->cost_method;
    }

    /**
     * Determine if the product must be tracked by lot: the category policy wins, otherwise the team setting applies.
     *
     * Untracked products that already have stock movements are exempt, because their tracking can no longer change.
     */
    public function requiresLotTracking(): bool
    {
        if ($this->tracking === ProductTracking::None && $this->exists && $this->hasStockMovements()) {
            return false;
        }

        return static::lotTrackingRequiredFor($this->team_id, $this->category_id);
    }

    /**
     * Determine if products of the category, or uncategorised products of the team, must be tracked by lot.
     */
    public static function lotTrackingRequiredFor(int $teamId, ?int $categoryId): bool
    {
        $policy = $categoryId === null ? null : ProductCategory::query()->whereKey($categoryId)->first()?->lot_tracking;

        if ($policy !== null) {
            return $policy === LotTrackingPolicy::Required;
        }

        return (bool) Team::query()->whereKey($teamId)->value('require_lot_tracking');
    }

    /**
     * Determine if any stock movement refers to the product.
     */
    public function hasStockMovements(): bool
    {
        return StockMovement::query()->where('product_id', $this->id)->exists();
    }

    /**
     * Determine if the stock of the product is tracked by lot.
     */
    public function isLotTracked(): bool
    {
        return $this->tracking === ProductTracking::Lot;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_purchase_price' => 'decimal:4',
            'default_sales_price' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'is_active' => 'boolean',
            'tracking' => ProductTracking::class,
        ];
    }
}
