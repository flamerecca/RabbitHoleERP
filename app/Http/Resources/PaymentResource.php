<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
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
            'payment_no' => $this->payment_no,
            'payment_type' => $this->payment_type->value,
            'partner_type' => $this->partner_type->value,
            'partner_id' => $this->partner_id,
            'journal_id' => $this->journal_id,
            'amount' => (float) $this->amount,
            'currency_id' => $this->currency_id,
            'exchange_rate' => (float) $this->exchange_rate,
            'payment_date' => $this->payment_date->toDateString(),
            'status' => $this->status->value,
            'journal_entry_id' => $this->journal_entry_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
