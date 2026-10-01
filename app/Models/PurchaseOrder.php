<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 採購單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $supplier_id
 * @property-read Supplier $supplier
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $currency_id
 * @property-read Currency $currency
 * @property string $exchange_rate
 * @property string $order_no
 * @property PurchaseOrderStatus $status
 * @property Carbon $order_date
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $total_amount
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PurchaseOrderItem> $items
 */
#[Fillable(['team_id', 'supplier_id', 'warehouse_id', 'currency_id', 'exchange_rate', 'order_no', 'status', 'order_date', 'subtotal', 'tax_amount', 'total_amount', 'created_by'])]
class PurchaseOrder extends Model implements HasActivityLog
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the purchase order.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the supplier of the purchase order.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the warehouse of the purchase order.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the currency of the purchase order.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the creator of the purchase order.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the purchase order.
     *
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Recalculate the subtotal, tax and total amounts from the line items.
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->with('taxRate')->get();
        $subtotal = $items->sum(fn (PurchaseOrderItem $item) => round((float) $item->quantity * (float) $item->unit_price, 4));
        $taxAmount = $items->sum(fn (PurchaseOrderItem $item) => round((float) $item->quantity * (float) $item->unit_price * (float) ($item->taxRate->rate ?? 0), 4));

        $this->update([
            'subtotal' => round($subtotal, 4),
            'tax_amount' => round($taxAmount, 4),
            'total_amount' => round($subtotal + $taxAmount, 4),
        ]);
    }

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['subtotal', 'tax_amount', 'total_amount'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:8',
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }
}
