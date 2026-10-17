<?php

namespace App\Models;

use Database\Factories\PosOrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * POS 訂單明細，快照下單當下的單價、定價、折扣與稅率。
 *
 * @property int $id
 * @property int $pos_order_id
 * @property-read PosOrder $order
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property string|null $list_price
 * @property string $unit_price
 * @property string|null $product_discount_rate
 * @property string $discount_rate
 * @property int|null $tax_rate_id
 * @property-read TaxRate|null $taxRate
 * @property string $tax_rate
 * @property string $line_total
 * @property string $line_tax
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'pos_order_id', 'product_id', 'quantity', 'list_price', 'unit_price', 'product_discount_rate', 'discount_rate',
    'tax_rate_id', 'tax_rate', 'line_total', 'line_tax',
])]
class PosOrderLine extends Model
{
    /** @use HasFactory<PosOrderLineFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discount_rate' => 0,
        'tax_rate' => 0,
    ];

    /**
     * Get the order of the line.
     *
     * @return BelongsTo<PosOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    /**
     * Get the product of the line.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the tax rate the line was priced with.
     *
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
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
            'list_price' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'product_discount_rate' => 'decimal:4',
            'discount_rate' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'line_total' => 'decimal:4',
            'line_tax' => 'decimal:4',
        ];
    }
}
