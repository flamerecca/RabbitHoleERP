<?php

namespace App\Models;

use App\Enums\CostMethod;
use App\Enums\LotTrackingPolicy;
use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 產品分類，支援多層分類。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int|null $parent_id
 * @property-read ProductCategory|null $parent
 * @property string $name
 * @property CostMethod $cost_method
 * @property LotTrackingPolicy|null $lot_tracking
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Product> $products
 */
#[Fillable(['team_id', 'parent_id', 'name', 'cost_method', 'lot_tracking'])]
class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;

    /**
     * Get the team of the product category.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the parent of the product category.
     *
     * @return BelongsTo<ProductCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * Get the products of the category.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_method' => CostMethod::class,
            'lot_tracking' => LotTrackingPolicy::class,
        ];
    }
}
