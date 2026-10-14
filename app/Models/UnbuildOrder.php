<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\UnbuildOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 拆解單主檔，確認後扣減成品庫存並把原料入庫。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $unbuild_no
 * @property int $product_id
 * @property-read Product $product
 * @property int $bom_id
 * @property-read Bom $bom
 * @property string $quantity
 * @property DocumentStatus $status
 * @property string|null $reason
 * @property Carbon|null $unbuilt_at
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, UnbuildOrderItem> $items
 */
#[Fillable(['team_id', 'unbuild_no', 'warehouse_id', 'product_id', 'bom_id', 'quantity', 'status', 'reason', 'unbuilt_at', 'created_by'])]
class UnbuildOrder extends Model implements HasActivityLog
{
    /** @use HasFactory<UnbuildOrderFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the team of the unbuild order.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the unbuild order.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the finished product of the unbuild order.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the bill of materials the unbuild order was expanded from.
     *
     * @return BelongsTo<Bom, $this>
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    /**
     * Get the creator of the unbuild order.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the unbuild order.
     *
     * @return HasMany<UnbuildOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(UnbuildOrderItem::class);
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
            'quantity' => 'decimal:4',
            'unbuilt_at' => 'datetime',
        ];
    }
}
