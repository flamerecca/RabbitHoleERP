<?php

namespace App\Http\Resources;

use App\Models\PaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaymentAllocation
 */
class PaymentAllocationResource extends JsonResource
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
            'payment_id' => $this->payment_id,
            'journal_entry_line_id' => $this->journal_entry_line_id,
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
