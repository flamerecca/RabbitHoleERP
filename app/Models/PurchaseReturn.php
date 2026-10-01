<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use Database\Factories\PurchaseReturnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 採購退貨單主檔，退貨去向固定為虛擬位置 Suppliers。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $supplier_id
 * @property-read Supplier $supplier
 * @property int $goods_receipt_id
 * @property-read GoodsReceipt $goodsReceipt
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property string $return_no
 * @property Carbon $return_date
 * @property DocumentStatus $status
 * @property string $reason
 * @property string $total_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PurchaseReturnItem> $items
 */
#[Fillable(['team_id', 'supplier_id', 'goods_receipt_id', 'warehouse_id', 'return_no', 'return_date', 'status', 'reason', 'total_amount'])]
class PurchaseReturn extends Model implements HasActivityLog
{
    /** @use HasFactory<PurchaseReturnFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the team of the purchase return.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the supplier of the purchase return.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the goods receipt of the purchase return.
     *
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * Get the warehouse of the purchase return.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the line items of the purchase return.
     *
     * @return HasMany<PurchaseReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['total_amount'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'status' => DocumentStatus::class,
            'total_amount' => 'decimal:4',
        ];
    }
}
