<?php

namespace App\Actions\Accounting;

use App\Enums\DocumentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class UpdatePayment
{
    /**
     * Update the given fields of a draft payment.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Payment $payment, array $attributes): Payment
    {
        return DB::transaction(function () use ($payment, $attributes) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            abort_if($payment->status !== DocumentStatus::Draft, 409, __('Only draft payments can be updated.'));

            $payment->update($attributes);

            return $payment;
        });
    }
}
