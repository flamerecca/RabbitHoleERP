<?php

namespace App\Models;

use Database\Factories\BomItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 物料清單的原料明細。
 *
 * @property int $id
 * @property int $bom_id
 * @property-read Bom $bom
 * @property int $component_product_id
 * @property-read Product $component
 * @property string $quantity
 * @property int $unit_id
 * @property-read Unit $unit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['bom_id', 'component_product_id', 'quantity', 'unit_id'])]
class BomItem extends Model
{
    /** @use HasFactory<BomItemFactory> */
    use HasFactory;

    /**
     * Get the bill of materials of the item.
     *
     * @return BelongsTo<Bom, $this>
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    /**
     * Get the component product of the item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    /**
     * Get the unit of the item quantity.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
