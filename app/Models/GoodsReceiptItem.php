<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\GoodsReceiptItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 進貨單明細。
 *
 * @property int $id
 * @property int $goods_receipt_id
 * @property-read GoodsReceipt $goodsReceipt
 * @property int $purchase_order_item_id
 * @property-read PurchaseOrderItem $purchaseOrderItem
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property string $unit_cost
 * @property string|null $lot_no
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['goods_receipt_id', 'purchase_order_item_id', 'product_id', 'quantity', 'unit_cost', 'lot_no'])]
class GoodsReceiptItem extends Model implements HasActivityLog
{
    /** @use HasFactory<GoodsReceiptItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the goods receipt of the goods receipt item.
     *
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * Get the purchase order item of the goods receipt item.
     *
     * @return BelongsTo<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Get the product of the goods receipt item.
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
        return $this->goodsReceipt()->firstOrFail();
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
            'unit_cost' => 'decimal:4',
        ];
    }
}
