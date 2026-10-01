<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\StockTakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 盤點單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $take_no
 * @property DocumentStatus $status
 * @property Carbon $taken_date
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, StockTakeItem> $items
 */
#[Fillable(['team_id', 'warehouse_id', 'take_no', 'status', 'taken_date', 'created_by'])]
class StockTake extends Model implements HasActivityLog
{
    /** @use HasFactory<StockTakeFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the stock take.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the stock take.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the creator of the stock take.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the stock take.
     *
     * @return HasMany<StockTakeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTakeItem::class);
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
            'taken_date' => 'date',
        ];
    }
}
