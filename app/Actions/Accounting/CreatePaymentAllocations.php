<?php

namespace App\Actions\Accounting;

use App\Enums\AccountType;
use App\Enums\DocumentStatus;
use App\Enums\JournalEntryStatus;
use App\Enums\PaymentType;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePaymentAllocations
{
    /**
     * Settle open receivable or payable lines of the payment's partner with a confirmed payment, in base currency.
     *
     * Receipts settle debit lines of receivable accounts and payments credit lines of payable accounts. Either every
     * allocation is written or none.
     *
     * @param  list<array{journal_entry_line_id: int, amount: float|int|string}>  $allocations
     * @return Collection<int, PaymentAllocation>
     *
     * @throws ValidationException
     */
    public function handle(Payment $payment, array $allocations): Collection
    {
        return DB::transaction(function () use ($payment, $allocations) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            abort_if($payment->status !== DocumentStatus::Confirmed, 409, __('Only confirmed payments can be allocated.'));

            $isReceipt = $payment->payment_type === PaymentType::Receipt;
            $lines = JournalEntryLine::query()
                ->with(['account', 'journalEntry'])
                ->whereKey(array_column($allocations, 'journal_entry_line_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($allocations as $index => $allocation) {
                $line = $lines->get($allocation['journal_entry_line_id']);
                $isSettleable = $line !== null
                    && $line->journalEntry->team_id === $payment->team_id
                    && $line->journalEntry->status === JournalEntryStatus::Posted
                    && $line->partner_type === $payment->partner_type
                    && $line->partner_id === $payment->partner_id
                    && $line->account->account_type === ($isReceipt ? AccountType::Receivable : AccountType::Payable)
                    && (float) ($isReceipt ? $line->debit : $line->credit) > 0;

                if (! $isSettleable) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.journal_entry_line_id" => __('The line is not an open receivable or payable of the payment partner.'),
                    ]);
                }

                abort_if(round((float) $allocation['amount'], 4) > (float) $line->amount_residual, 409, __('The allocated amount exceeds the residual of the line.'));
            }

            $allocated = (float) $payment->allocations()->sum('amount');
            abort_if(
                round($allocated + array_sum(array_map(fn (array $allocation) => (float) $allocation['amount'], $allocations)), 4) > $payment->baseAmount(),
                409,
                __('The allocated amount exceeds the unallocated amount of the payment.'),
            );

            $created = new Collection();
            foreach ($allocations as $allocation) {
                $line = $lines[$allocation['journal_entry_line_id']];
                $amount = round((float) $allocation['amount'], 4);

                $line->update(['amount_residual' => round((float) $line->amount_residual - $amount, 4)]);
                $created->push($payment->allocations()->create(['journal_entry_line_id' => $line->id, 'amount' => $amount]));
            }

            return $created;
        });
    }
}
