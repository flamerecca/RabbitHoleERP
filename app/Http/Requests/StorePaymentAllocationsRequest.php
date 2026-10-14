<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentAllocationsRequest extends FormRequest
{
    /**
     * Get the validation rules for allocating a payment to receivable or payable lines.
     *
     * Whether each line can be settled by the payment is checked by `CreatePaymentAllocations`.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.journal_entry_line_id' => ['required', 'integer', 'distinct'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
