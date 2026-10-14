<?php

namespace App\Actions\Accounting;

use App\Enums\DocumentStatus;
use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CancelPayment
{
    /**
     * Cancel a draft or confirmed payment. A confirmed payment gives back the residual of every line it settled,
     * deletes its allocations and cancels its journal entry.
     */
    public function handle(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            abort_if($payment->status === DocumentStatus::Cancelled, 409, __('The payment is already cancelled.'));

            foreach ($payment->allocations()->get() as $allocation) {
                $line = JournalEntryLine::query()->lockForUpdate()->findOrFail($allocation->journal_entry_line_id);
                $line->update(['amount_residual' => round((float) $line->amount_residual + (float) $allocation->amount, 4)]);
                $allocation->delete();
            }

            if ($payment->journal_entry_id !== null) {
                JournalEntry::query()->findOrFail($payment->journal_entry_id)->update(['status' => JournalEntryStatus::Cancelled]);
            }

            $payment->update(['status' => DocumentStatus::Cancelled]);

            return $payment;
        });
    }
}
