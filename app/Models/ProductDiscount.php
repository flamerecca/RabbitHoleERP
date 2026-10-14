<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\ProductDiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 商品折扣，折扣比例為百分比，期間含起迄兩天。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $product_id
 * @property-read Product $product
 * @property string $discount_rate
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'product_id', 'discount_rate', 'starts_on', 'ends_on', 'is_active'])]
class ProductDiscount extends Model implements HasActivityLog
{
    /** @use HasFactory<ProductDiscountFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the team of the discount.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the product of the discount.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Limit to the active discounts of the product whose period touches the given days, boundaries included.
     *
     * @param  Builder<ProductDiscount>  $query
     * @return Builder<ProductDiscount>
     */
    public function scopeOverlapping(Builder $query, int $productId, string $startsOn, string $endsOn, ?int $ignoreId = null): Builder
    {
        return $query->where('product_id', $productId)
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', $endsOn)
            ->whereDate('ends_on', '>=', $startsOn)
            ->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_rate' => 'decimal:4',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
