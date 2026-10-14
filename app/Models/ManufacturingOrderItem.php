<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\ManufacturingOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 生產單明細。
 *
 * @property int $id
 * @property int $manufacturing_order_id
 * @property-read ManufacturingOrder $manufacturingOrder
 * @property int $component_product_id
 * @property-read Product $component
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['manufacturing_order_id', 'component_product_id', 'quantity'])]
class ManufacturingOrderItem extends Model implements HasActivityLog
{
    /** @use HasFactory<ManufacturingOrderItemFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the manufacturing order of the manufacturing order item.
     *
     * @return BelongsTo<ManufacturingOrder, $this>
     */
    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    /**
     * Get the component product of the manufacturing order item.
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
        return $this->manufacturingOrder()->firstOrFail();
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
