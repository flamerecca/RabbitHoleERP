<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\StockTakeItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 盤點明細。
 *
 * @property int $id
 * @property int $stock_take_id
 * @property-read StockTake $stockTake
 * @property int $product_id
 * @property int|null $stock_lot_id
 * @property-read StockLot|null $stockLot
 * @property-read Product $product
 * @property string $system_quantity
 * @property string|null $counted_quantity
 * @property string|null $difference
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['stock_take_id', 'product_id', 'stock_lot_id', 'system_quantity', 'counted_quantity', 'difference'])]
class StockTakeItem extends Model implements HasActivityLog
{
    /** @use HasFactory<StockTakeItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the stock take of the stock take item.
     *
     * @return BelongsTo<StockTake, $this>
     */
    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    /**
     * Get the product of the stock take item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the lot of the line.
     *
     * @return BelongsTo<StockLot, $this>
     */
    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }

    /**
     * Get the record the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->stockTake()->firstOrFail();
    }

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['system_quantity', 'difference'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
            'difference' => 'decimal:4',
        ];
    }
}
