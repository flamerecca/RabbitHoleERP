<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use Database\Factories\QuotationTemplateItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 報價單範本明細，只記錄商品與預設數量。
 *
 * @property int $id
 * @property int $quotation_template_id
 * @property-read QuotationTemplate $quotationTemplate
 * @property int $product_id
 * @property-read Product $product
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['quotation_template_id', 'product_id', 'quantity'])]
class QuotationTemplateItem extends Model implements HasActivityLog
{
    /** @use HasFactory<QuotationTemplateItemFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the template of the line.
     *
     * @return BelongsTo<QuotationTemplate, $this>
     */
    public function quotationTemplate(): BelongsTo
    {
        return $this->belongsTo(QuotationTemplate::class);
    }

    /**
     * Get the product of the line.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the template the activity of the line belongs to.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this->quotationTemplate()->firstOrFail();
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
