<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\PurchaseReturnItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 採購退貨明細。
 *
 * @property int $id
 * @property int $purchase_return_id
 * @property-read PurchaseReturn $purchaseReturn
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property string $unit_cost
 * @property int|null $stock_lot_id
 * @property-read StockLot|null $stockLot
 * @property bool $is_whole_lot
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['purchase_return_id', 'product_id', 'stock_lot_id', 'is_whole_lot', 'quantity', 'unit_cost'])]
class PurchaseReturnItem extends Model implements HasActivityLog
{
    /** @use HasFactory<PurchaseReturnItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the purchase return of the purchase return item.
     *
     * @return BelongsTo<PurchaseReturn, $this>
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    /**
     * Get the product of the purchase return item.
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
        return $this->purchaseReturn()->firstOrFail();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_whole_lot' => 'boolean',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }
}
