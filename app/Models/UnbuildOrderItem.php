<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\UnbuildOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 拆解單明細。
 *
 * @property int $id
 * @property int $unbuild_order_id
 * @property-read UnbuildOrder $unbuildOrder
 * @property int $component_product_id
 * @property-read Product $component
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['unbuild_order_id', 'component_product_id', 'quantity'])]
class UnbuildOrderItem extends Model implements HasActivityLog
{
    /** @use HasFactory<UnbuildOrderItemFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the unbuild order of the unbuild order item.
     *
     * @return BelongsTo<UnbuildOrder, $this>
     */
    public function unbuildOrder(): BelongsTo
    {
        return $this->belongsTo(UnbuildOrder::class);
    }

    /**
     * Get the component product of the unbuild order item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    /**
     * Get the record the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->unbuildOrder()->firstOrFail();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }
}
