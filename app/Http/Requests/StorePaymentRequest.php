<?php

namespace App\Http\Requests;

use App\Enums\PartnerType;
use App\Enums\PaymentType;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Get the validation rules for creating a draft payment: receipts come from customers, payments go to suppliers.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');
        $paymentType = PaymentType::tryFrom((string) $this->input('payment_type'));
        $partnerTable = PartnerType::tryFrom((string) $this->input('partner_type')) === PartnerType::Customer ? 'customers' : 'suppliers';

        return [
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'partner_type' => ['required', Rule::enum(PartnerType::class), Rule::in($paymentType === null ? [] : [$paymentType->partnerType()->value])],
            'partner_id' => ['required', 'integer', Rule::exists($partnerTable, 'id')->where('team_id', $team->id)],
            ...UpdatePaymentRequest::paymentRules($team, 'required'),
        ];
    }
}
