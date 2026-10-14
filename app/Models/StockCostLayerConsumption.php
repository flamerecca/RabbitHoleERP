<?php

namespace App\Models;

use Database\Factories\StockCostLayerConsumptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 成本層扣減紀錄，記錄一筆減少庫存的異動從某一成本層扣減的數量與當下單位成本。
 *
 * @property int $id
 * @property int $stock_cost_layer_id
 * @property int $stock_movement_id
 * @property string $quantity
 * @property string $unit_cost
 * @property Carbon|null $created_at
 * @property-read StockCostLayer $stockCostLayer
 * @property-read StockMovement $stockMovement
 */
#[Fillable(['stock_cost_layer_id', 'stock_movement_id', 'quantity', 'unit_cost'])]
class StockCostLayerConsumption extends Model
{
    /** @use HasFactory<StockCostLayerConsumptionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the cost layer the quantity was taken from.
     *
     * @return BelongsTo<StockCostLayer, $this>
     */
    public function stockCostLayer(): BelongsTo
    {
        return $this->belongsTo(StockCostLayer::class);
    }

    /**
     * Get the stock movement that consumed the quantity.
     *
     * @return BelongsTo<StockMovement, $this>
     */
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
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
            'created_at' => 'datetime',
        ];
    }
}
