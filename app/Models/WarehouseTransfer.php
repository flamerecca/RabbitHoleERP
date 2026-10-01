<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\WarehouseTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 倉庫調撥單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $from_warehouse_id
 * @property-read Warehouse $fromWarehouse
 * @property int $to_warehouse_id
 * @property-read Warehouse $toWarehouse
 * @property string $transfer_no
 * @property DocumentStatus $status
 * @property Carbon $transfer_date
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WarehouseTransferItem> $items
 */
#[Fillable(['team_id', 'from_warehouse_id', 'to_warehouse_id', 'transfer_no', 'status', 'transfer_date', 'created_by'])]
class WarehouseTransfer extends Model implements HasActivityLog
{
    /** @use HasFactory<WarehouseTransferFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the warehouse transfer.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the from warehouse of the warehouse transfer.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the to warehouse of the warehouse transfer.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the creator of the warehouse transfer.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the warehouse transfer.
     *
     * @return HasMany<WarehouseTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'transfer_date' => 'date',
        ];
    }
}
