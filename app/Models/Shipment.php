<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 出貨單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $sales_order_id
 * @property-read SalesOrder $salesOrder
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $shipment_no
 * @property Carbon $shipped_date
 * @property DocumentStatus $status
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ShipmentItem> $items
 */
#[Fillable(['team_id', 'sales_order_id', 'warehouse_id', 'shipment_no', 'shipped_date', 'status', 'created_by'])]
class Shipment extends Model implements HasActivityLog
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the shipment.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the sales order of the shipment.
     *
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the warehouse of the shipment.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the creator of the shipment.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the shipment.
     *
     * @return HasMany<ShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipped_date' => 'date',
            'status' => DocumentStatus::class,
        ];
    }
}
