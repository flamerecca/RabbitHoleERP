<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\ProductSupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 供應商報價，單價與最低訂購量皆以產品的採購單位計算。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $product_id
 * @property-read Product $product
 * @property int $supplier_id
 * @property-read Supplier $supplier
 * @property string|null $supplier_product_code
 * @property string|null $supplier_product_name
 * @property int $currency_id
 * @property-read Currency $currency
 * @property string $unit_price
 * @property string $min_quantity
 * @property int $lead_time_days
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property int $sequence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'product_id', 'supplier_id', 'supplier_product_code', 'supplier_product_name', 'currency_id', 'unit_price', 'min_quantity', 'lead_time_days', 'valid_from', 'valid_until', 'sequence'])]
class ProductSupplier extends Model implements HasActivityLog
{
    /** @use HasFactory<ProductSupplierFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the product supplier.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the product of the product supplier.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier of the product supplier.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the currency of the product supplier.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the record the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->product()->firstOrFail();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'min_quantity' => 'decimal:4',
            'lead_time_days' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'sequence' => 'integer',
        ];
    }
}
