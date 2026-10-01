<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 進貨單主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $purchase_order_id
 * @property-read PurchaseOrder $purchaseOrder
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $receipt_no
 * @property Carbon $received_date
 * @property DocumentStatus $status
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, GoodsReceiptItem> $items
 */
#[Fillable(['team_id', 'purchase_order_id', 'warehouse_id', 'receipt_no', 'received_date', 'status', 'created_by'])]
class GoodsReceipt extends Model implements HasActivityLog
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the goods receipt.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the purchase order of the goods receipt.
     *
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the warehouse of the goods receipt.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the creator of the goods receipt.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the line items of the goods receipt.
     *
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'status' => DocumentStatus::class,
        ];
    }
}
