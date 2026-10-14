<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\DocumentStatus;
use App\Enums\PartnerType;
use App\Enums\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 收付款單，對應 Odoo 的 account.payment，確認時過帳並寫入 journal_entry_id。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $payment_no
 * @property PaymentType $payment_type
 * @property PartnerType $partner_type
 * @property int $partner_id
 * @property int $journal_id
 * @property-read Journal $journal
 * @property string $amount
 * @property int $currency_id
 * @property-read Currency $currency
 * @property string $exchange_rate
 * @property Carbon $payment_date
 * @property DocumentStatus $status
 * @property int|null $journal_entry_id
 * @property-read JournalEntry|null $journalEntry
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PaymentAllocation> $allocations
 */
#[Fillable([
    'team_id', 'payment_no', 'payment_type', 'partner_type', 'partner_id', 'journal_id', 'amount', 'currency_id',
    'exchange_rate', 'payment_date', 'status', 'journal_entry_id', 'created_by',
])]
class Payment extends Model implements HasActivityLog
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the team of the payment.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the cash or bank journal of the payment.
     *
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the currency of the payment.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entry posted when the payment was confirmed.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the receivable and payable lines the payment settles.
     *
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the payment amount in the base currency.
     */
    public function baseAmount(): float
    {
        return round((float) $this->amount * (float) $this->exchange_rate, 4);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'partner_type' => PartnerType::class,
            'amount' => 'decimal:4',
            'exchange_rate' => 'decimal:8',
            'payment_date' => 'date',
            'status' => DocumentStatus::class,
        ];
    }
}
