<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\ScrapOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 報廢單主檔，確認後扣減庫存，去向為虛擬位置 Scrap。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $scrap_no
 * @property DocumentStatus $status
 * @property string|null $reason
 * @property Carbon|null $scrapped_at
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ScrapOrderItem> $items
 */
#[Fillable(['team_id', 'warehouse_id', 'scrap_no', 'status', 'reason', 'scrapped_at', 'created_by'])]
class ScrapOrder extends Model implements HasActivityLog
{
    /** @use HasFactory<ScrapOrderFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the team of the scrap order.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the scrap order.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the creator of the scrap order.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the scrap order.
     *
     * @return HasMany<ScrapOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ScrapOrderItem::class);
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
            'scrapped_at' => 'datetime',
        ];
    }
}
