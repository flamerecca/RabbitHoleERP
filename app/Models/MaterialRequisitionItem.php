<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\MaterialRequisitionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 領料單明細。
 *
 * @property int $id
 * @property int $material_requisition_id
 * @property-read MaterialRequisition $materialRequisition
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['material_requisition_id', 'product_id', 'quantity', 'note'])]
class MaterialRequisitionItem extends Model implements HasActivityLog
{
    /** @use HasFactory<MaterialRequisitionItemFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Get the material requisition of the material requisition item.
     *
     * @return BelongsTo<MaterialRequisition, $this>
     */
    public function materialRequisition(): BelongsTo
    {
        return $this->belongsTo(MaterialRequisition::class);
    }

    /**
     * Get the product of the material requisition item.
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
        return $this->materialRequisition()->firstOrFail();
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
