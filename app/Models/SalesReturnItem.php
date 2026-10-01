<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\SalesReturnDisposition;
use Database\Factories\SalesReturnItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 銷售退貨明細，disposition 決定退貨去向。
 *
 * @property int $id
 * @property int $sales_return_id
 * @property-read SalesReturn $salesReturn
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property SalesReturnDisposition $disposition
 * @property int|null $stock_lot_id
 * @property-read StockLot|null $stockLot
 * @property bool $is_whole_lot
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sales_return_id', 'product_id', 'stock_lot_id', 'is_whole_lot', 'quantity', 'disposition'])]
class SalesReturnItem extends Model implements HasActivityLog
{
    /** @use HasFactory<SalesReturnItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the sales return of the sales return item.
     *
     * @return BelongsTo<SalesReturn, $this>
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * Get the product of the sales return item.
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
        return $this->salesReturn()->firstOrFail();
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
            'disposition' => SalesReturnDisposition::class,
        ];
    }
}
