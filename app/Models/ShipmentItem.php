<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\ShipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * 出貨單明細。
 *
 * @property int $id
 * @property int $shipment_id
 * @property-read Shipment $shipment
 * @property int $sales_order_item_id
 * @property-read SalesOrderItem $salesOrderItem
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property int|null $stock_lot_id
 * @property-read StockLot|null $stockLot
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['shipment_id', 'sales_order_item_id', 'product_id', 'quantity', 'stock_lot_id'])]
class ShipmentItem extends Model implements HasActivityLog
{
    /** @use HasFactory<ShipmentItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the shipment of the shipment item.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the sales order item of the shipment item.
     *
     * @return BelongsTo<SalesOrderItem, $this>
     */
    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    /**
     * Get the product of the shipment item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock movements the line wrote, one per lot it shipped from.
     *
     * @return MorphMany<StockMovement, $this>
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
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
        return $this->shipment()->firstOrFail();
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
