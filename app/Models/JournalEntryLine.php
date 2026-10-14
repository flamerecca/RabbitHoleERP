<?php

namespace App\Models;

use App\Enums\PartnerType;
use Database\Factories\JournalEntryLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 分錄明細，對應 Odoo 的 account.move.line，debit、credit 為基準幣別金額。
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property-read JournalEntry $journalEntry
 * @property int $account_id
 * @property-read ChartOfAccount $account
 * @property PartnerType|null $partner_type
 * @property int|null $partner_id
 * @property string $debit
 * @property string $credit
 * @property int|null $currency_id
 * @property string|null $exchange_rate
 * @property string|null $amount_currency
 * @property Carbon|null $due_date
 * @property string $amount_residual
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'journal_entry_id', 'account_id', 'partner_type', 'partner_id', 'debit', 'credit', 'currency_id',
    'exchange_rate', 'amount_currency', 'due_date', 'amount_residual', 'description',
])]
class JournalEntryLine extends Model
{
    /** @use HasFactory<JournalEntryLineFactory> */
    use HasFactory;

    /**
     * Get the journal entry of the line.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the account of the line.
     *
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'partner_type' => PartnerType::class,
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'exchange_rate' => 'decimal:8',
            'amount_currency' => 'decimal:4',
            'due_date' => 'date',
            'amount_residual' => 'decimal:4',
        ];
    }
}
