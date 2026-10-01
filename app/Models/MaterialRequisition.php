<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\MaterialRequisitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 領料單主檔，確認後扣減庫存，去向為虛擬位置 Production。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $requisition_no
 * @property Carbon $requisition_date
 * @property string $purpose
 * @property int $requested_by
 * @property-read User $requester
 * @property DocumentStatus $status
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, MaterialRequisitionItem> $items
 */
#[Fillable(['team_id', 'warehouse_id', 'requisition_no', 'requisition_date', 'purpose', 'requested_by', 'status', 'created_by'])]
class MaterialRequisition extends Model implements HasActivityLog
{
    /** @use HasFactory<MaterialRequisitionFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the material requisition.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the material requisition.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the requester of the material requisition.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the creator of the material requisition.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the material requisition.
     *
     * @return HasMany<MaterialRequisitionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequisitionItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requisition_date' => 'date',
            'status' => DocumentStatus::class,
        ];
    }
}
