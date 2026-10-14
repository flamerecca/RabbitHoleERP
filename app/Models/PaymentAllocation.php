<?php

namespace App\Models;

use Database\Factories\PaymentAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 收付款與應收應付分錄明細的配對，對應 Odoo 的 account.partial.reconcile，金額為基準幣別。
 *
 * @property int $id
 * @property int $payment_id
 * @property-read Payment $payment
 * @property int $journal_entry_line_id
 * @property-read JournalEntryLine $journalEntryLine
 * @property string $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['payment_id', 'journal_entry_line_id', 'amount'])]
class PaymentAllocation extends Model
{
    /** @use HasFactory<PaymentAllocationFactory> */
    use HasFactory;

    /**
     * Get the payment of the allocation.
     *
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the receivable or payable line the allocation settles.
     *
     * @return BelongsTo<JournalEntryLine, $this>
     */
    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class);
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
        ];
    }
}
