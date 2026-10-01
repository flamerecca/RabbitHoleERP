<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\ConsignmentHoldStatus;
use Database\Factories\ConsignmentHoldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 寄倉單主檔，記錄已出貨但客戶暫不取貨、代為保管的商品。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $shipment_id
 * @property-read Shipment $shipment
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $hold_no
 * @property Carbon $held_from
 * @property Carbon $held_until
 * @property ConsignmentHoldStatus $status
 * @property Carbon|null $picked_up_at
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'shipment_id', 'warehouse_id', 'hold_no', 'held_from', 'held_until', 'status', 'picked_up_at', 'created_by'])]
class ConsignmentHold extends Model implements HasActivityLog
{
    /** @use HasFactory<ConsignmentHoldFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the consignment hold.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the shipment of the consignment hold.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the warehouse of the consignment hold.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the creator of the consignment hold.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'held_from' => 'date',
            'held_until' => 'date',
            'status' => ConsignmentHoldStatus::class,
            'picked_up_at' => 'datetime',
        ];
    }
}
