<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\ManufacturingOrderStatus;
use Database\Factories\ManufacturingOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 生產單主檔，完成時扣減原料庫存並讓成品入庫。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $mo_no
 * @property int $product_id
 * @property-read Product $product
 * @property int $bom_id
 * @property-read Bom $bom
 * @property string $quantity
 * @property ManufacturingOrderStatus $status
 * @property Carbon|null $planned_date
 * @property Carbon|null $completed_at
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ManufacturingOrderItem> $items
 */
#[Fillable(['team_id', 'mo_no', 'product_id', 'bom_id', 'warehouse_id', 'quantity', 'status', 'planned_date', 'completed_at', 'created_by'])]
class ManufacturingOrder extends Model implements HasActivityLog
{
    /** @use HasFactory<ManufacturingOrderFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the team of the manufacturing order.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the manufacturing order.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the finished product of the manufacturing order.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the bill of materials the manufacturing order was expanded from.
     *
     * @return BelongsTo<Bom, $this>
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    /**
     * Get the creator of the manufacturing order.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the manufacturing order.
     *
     * @return HasMany<ManufacturingOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ManufacturingOrderItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ManufacturingOrderStatus::class,
            'quantity' => 'decimal:4',
            'planned_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
