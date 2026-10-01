<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\SalesReturnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 銷售退貨單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $customer_id
 * @property-read Customer $customer
 * @property int $shipment_id
 * @property-read Shipment $shipment
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $return_no
 * @property Carbon $return_date
 * @property DocumentStatus $status
 * @property string $reason
 * @property string $total_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SalesReturnItem> $items
 */
#[Fillable(['team_id', 'customer_id', 'shipment_id', 'warehouse_id', 'return_no', 'return_date', 'status', 'reason', 'total_amount'])]
class SalesReturn extends Model implements HasActivityLog
{
    /** @use HasFactory<SalesReturnFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the sales return.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the customer of the sales return.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the shipment of the sales return.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the warehouse of the sales return.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the line items of the sales return.
     *
     * @return HasMany<SalesReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['total_amount'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'status' => DocumentStatus::class,
            'total_amount' => 'decimal:4',
        ];
    }
}
