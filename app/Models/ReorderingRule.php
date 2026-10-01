<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\ReorderingRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 補貨規則，數量皆以產品的庫存單位計算。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $product_id
 * @property-read Product $product
 * @property string $min_quantity
 * @property string $max_quantity
 * @property string $multiple_quantity
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'warehouse_id', 'product_id', 'min_quantity', 'max_quantity', 'multiple_quantity', 'is_active'])]
class ReorderingRule extends Model implements HasActivityLog
{
    /** @use HasFactory<ReorderingRuleFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the reordering rule.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the reordering rule.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product of the reordering rule.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the record the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->product()->firstOrFail();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'decimal:4',
            'max_quantity' => 'decimal:4',
            'multiple_quantity' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
