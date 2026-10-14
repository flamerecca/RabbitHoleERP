<?php

namespace App\Models;

use Database\Factories\StockCostLayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 先進先出成本層，每一筆增加庫存的異動建立一層，出庫時依序扣減剩餘數量。
 *
 * @property int $id
 * @property int $team_id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int $stock_movement_id
 * @property string $quantity
 * @property string $unit_cost
 * @property string $remaining_quantity
 * @property Carbon|null $created_at
 * @property-read StockMovement $stockMovement
 * @property-read Collection<int, StockCostLayerConsumption> $consumptions
 */
#[Fillable(['team_id', 'warehouse_id', 'product_id', 'stock_movement_id', 'quantity', 'unit_cost', 'remaining_quantity'])]
class StockCostLayer extends Model
{
    /** @use HasFactory<StockCostLayerFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the stock movement that created the layer.
     *
     * @return BelongsTo<StockMovement, $this>
     */
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    /**
     * Get the consumptions taken from the layer.
     *
     * @return HasMany<StockCostLayerConsumption, $this>
     */
    public function consumptions(): HasMany
    {
        return $this->hasMany(StockCostLayerConsumption::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }
}
