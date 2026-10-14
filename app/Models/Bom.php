<?php

namespace App\Models;

use Database\Factories\BomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 物料清單，數量以產品的庫存單位計算。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property bool $is_active
 * @property-read Collection<int, BomItem> $items
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'product_id', 'quantity', 'is_active'])]
class Bom extends Model
{
    /** @use HasFactory<BomFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the team of the bill of materials.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the finished product of the bill of materials.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the components of the bill of materials.
     *
     * @return HasMany<BomItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
