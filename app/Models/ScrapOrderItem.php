<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\ScrapOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 報廢單明細。
 *
 * @property int $id
 * @property int $scrap_order_id
 * @property-read ScrapOrder $scrapOrder
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['scrap_order_id', 'product_id', 'quantity'])]
class ScrapOrderItem extends Model implements HasActivityLog
{
    /** @use HasFactory<ScrapOrderItemFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the scrap order of the scrap order item.
     *
     * @return BelongsTo<ScrapOrder, $this>
     */
    public function scrapOrder(): BelongsTo
    {
        return $this->belongsTo(ScrapOrder::class);
    }

    /**
     * Get the product of the scrap order item.
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
        return $this->scrapOrder()->firstOrFail();
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
