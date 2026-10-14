<?php

namespace App\Actions\Accounting;

use App\Enums\DocumentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreatePayment
{
    public function __construct(protected DocumentSequence $documentSequence)
    {
    }

    /**
     * Create a draft receipt or payment, numbered by the sequence of its payment type.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, User $user, array $attributes): Payment
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $paymentType = PaymentType::from($attributes['payment_type']);

            return Payment::create([
                ...$attributes,
                'team_id' => $team->id,
                'payment_no' => $this->documentSequence->next($team, $paymentType->documentType(), Carbon::parse($attributes['payment_date'])),
                'status' => DocumentStatus::Draft,
                'created_by' => $user->id,
            ]);
        });
    }
}
