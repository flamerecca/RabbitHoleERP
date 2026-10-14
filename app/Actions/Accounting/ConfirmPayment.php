<?php

namespace App\Actions\Accounting;

use App\Enums\DocumentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmPayment
{
    public function __construct(protected PostJournalEntry $postJournalEntry)
    {
    }

    /**
     * Confirm a draft payment and post it: a receipt debits the cash or bank account of its journal and credits the
     * customer's receivable, a payment debits the supplier's payable and credits the cash or bank account.
     *
     * The receivable or payable line stays closed, as the payment itself tracks the amount not allocated yet.
     */
    public function handle(Payment $payment, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $user) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            abort_if($payment->status !== DocumentStatus::Draft, 409, __('Only draft payments can be confirmed.'));

            $payment->load(['team', 'journal']);
            $settings = PostJournalEntry::settings($payment->team);
            $cashAccountId = $payment->journal->default_account_id;

            abort_if($cashAccountId === null, 409, __('The journal of the payment has no default account.'));

            $isReceipt = $payment->payment_type === PaymentType::Receipt;
            $amount = $payment->baseAmount();

            abort_if($amount <= 0, 409, __('The payment amount in base currency rounds to zero.'));
            $partnerLine = [
                'account_id' => $isReceipt ? $settings->receivable_account_id : $settings->payable_account_id,
                'partner_type' => $payment->partner_type,
                'partner_id' => $payment->partner_id,
                'currency_id' => $payment->currency_id,
                'exchange_rate' => (float) $payment->exchange_rate,
                'amount_currency' => (float) $payment->amount,
                'open' => false,
            ];

            $entry = $this->postJournalEntry->handle($payment, $payment->team, $payment->journal_id, $payment->payment_date, $user, $isReceipt
                ? [['account_id' => $cashAccountId, 'debit' => $amount], [...$partnerLine, 'credit' => $amount]]
                : [[...$partnerLine, 'debit' => $amount], ['account_id' => $cashAccountId, 'credit' => $amount]], $payment->payment_no);

            $payment->update(['status' => DocumentStatus::Confirmed, 'journal_entry_id' => $entry?->id]);

            return $payment;
        });
    }
}
