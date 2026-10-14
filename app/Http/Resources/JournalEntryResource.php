<?php

namespace App\Http\Resources;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JournalEntry
 */
class JournalEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'journal_id' => $this->journal_id,
            'entry_no' => $this->entry_no,
            'entry_date' => $this->entry_date->toDateString(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'status' => $this->status->value,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'lines' => $this->lines->map(fn (JournalEntryLine $line) => [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'partner_type' => $line->partner_type?->value,
                'partner_id' => $line->partner_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'currency_id' => $line->currency_id,
                'exchange_rate' => $line->exchange_rate === null ? null : (float) $line->exchange_rate,
                'amount_currency' => $line->amount_currency === null ? null : (float) $line->amount_currency,
                'due_date' => $line->due_date?->toDateString(),
                'amount_residual' => (float) $line->amount_residual,
                'description' => $line->description,
            ]),
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
