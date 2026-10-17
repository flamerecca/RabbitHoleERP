<?php

namespace App\Models;

use Database\Factories\PosPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * POS 付款，一張訂單可以分多筆付款。
 *
 * @property int $id
 * @property int $pos_order_id
 * @property-read PosOrder $order
 * @property int $pos_payment_method_id
 * @property-read PosPaymentMethod $paymentMethod
 * @property string $amount
 * @property Carbon $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['pos_order_id', 'pos_payment_method_id', 'amount', 'paid_at'])]
class PosPayment extends Model
{
    /** @use HasFactory<PosPaymentFactory> */
    use HasFactory;

    /**
     * Get the order the payment belongs to.
     *
     * @return BelongsTo<PosOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    /**
     * Get the payment method of the payment.
     *
     * @return BelongsTo<PosPaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PosPaymentMethod::class, 'pos_payment_method_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }
}
