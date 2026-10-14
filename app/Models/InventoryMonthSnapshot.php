<?php

namespace App\Models;

use Database\Factories\InventoryMonthSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 存貨月結快照，由 PostgreSQL 程序 close_inventory_month 寫入，見 docs/architecture/inventory-closing.md。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $period
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $product_id
 * @property-read Product $product
 * @property string $opening_quantity
 * @property string $opening_amount
 * @property string $in_quantity
 * @property string $in_amount
 * @property string $out_quantity
 * @property string $out_amount
 * @property string $closing_quantity
 * @property string $closing_amount
 * @property string $average_cost
 * @property int|null $closed_by
 * @property Carbon $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'team_id', 'period', 'warehouse_id', 'product_id', 'opening_quantity', 'opening_amount', 'in_quantity', 'in_amount',
    'out_quantity', 'out_amount', 'closing_quantity', 'closing_amount', 'average_cost', 'closed_by', 'closed_at',
])]
class InventoryMonthSnapshot extends Model
{
    /** @use HasFactory<InventoryMonthSnapshotFactory> */
    use HasFactory;

    /**
     * Get the team of the snapshot.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the snapshot.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product of the snapshot.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_quantity' => 'decimal:4',
            'opening_amount' => 'decimal:4',
            'in_quantity' => 'decimal:4',
            'in_amount' => 'decimal:4',
            'out_quantity' => 'decimal:4',
            'out_amount' => 'decimal:4',
            'closing_quantity' => 'decimal:4',
            'closing_amount' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'closed_at' => 'datetime',
        ];
    }
}
