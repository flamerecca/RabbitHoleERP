<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\WarehouseTransferItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 調撥單明細。
 *
 * @property int $id
 * @property int $warehouse_transfer_id
 * @property-read WarehouseTransfer $warehouseTransfer
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['warehouse_transfer_id', 'product_id', 'quantity'])]
class WarehouseTransferItem extends Model implements HasActivityLog
{
    /** @use HasFactory<WarehouseTransferItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the warehouse transfer of the warehouse transfer item.
     *
     * @return BelongsTo<WarehouseTransfer, $this>
     */
    public function warehouseTransfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }

    /**
     * Get the product of the warehouse transfer item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the record the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->warehouseTransfer()->firstOrFail();
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
        ];
    }
}
