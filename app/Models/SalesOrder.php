<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\SalesOrderStatus;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 銷售訂單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $customer_id
 * @property-read Customer $customer
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $currency_id
 * @property-read Currency $currency
 * @property string $exchange_rate
 * @property string $order_no
 * @property SalesOrderStatus $status
 * @property Carbon $order_date
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $total_amount
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SalesOrderItem> $items
 */
#[Fillable(['team_id', 'customer_id', 'warehouse_id', 'currency_id', 'exchange_rate', 'order_no', 'status', 'order_date', 'subtotal', 'tax_amount', 'total_amount', 'created_by'])]
class SalesOrder extends Model implements HasActivityLog
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the sales order.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the customer of the sales order.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the warehouse of the sales order.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the currency of the sales order.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the creator of the sales order.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the sales order.
     *
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
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
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }
}
