<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\SalesOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 銷售訂單明細。
 *
 * @property int $id
 * @property int $sales_order_id
 * @property-read SalesOrder $salesOrder
 * @property int $product_id
 * @property-read Product $product
 * @property int $unit_id
 * @property-read Unit $unit
 * @property string $quantity
 * @property string $unit_price
 * @property int|null $tax_rate_id
 * @property-read TaxRate|null $taxRate
 * @property string $shipped_quantity
 * @property string $reserved_quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sales_order_id', 'product_id', 'unit_id', 'quantity', 'unit_price', 'tax_rate_id', 'shipped_quantity', 'reserved_quantity'])]
class SalesOrderItem extends Model implements HasActivityLog
{
    /** @use HasFactory<SalesOrderItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the sales order of the sales order item.
     *
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the product of the sales order item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit of the sales order item.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the tax rate of the sales order item.
     *
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Get the record the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->salesOrder()->firstOrFail();
    }

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['shipped_quantity', 'reserved_quantity'];
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
            'unit_price' => 'decimal:4',
            'shipped_quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
        ];
    }
}
