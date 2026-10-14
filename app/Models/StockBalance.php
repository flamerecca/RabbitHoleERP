<?php

namespace App\Models;

use Database\Factories\StockBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 每個倉庫加產品的即時庫存餘額。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity_on_hand
 * @property string $quantity_reserved
 * @property string $average_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'warehouse_id', 'product_id', 'quantity_on_hand', 'quantity_reserved', 'average_cost'])]
class StockBalance extends Model
{
    /** @use HasFactory<StockBalanceFactory> */
    use HasFactory;

    /**
     * Get the team of the stock balance.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the stock balance.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product of the stock balance.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'average_cost' => 'decimal:4',
        ];
    }
}
