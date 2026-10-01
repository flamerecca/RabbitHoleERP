<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
    /**
     * Get the validation rules that apply to the request, every field being optional.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (parent::rules() as $key => $rule) {
            $rules[$key] = str_starts_with($key, 'items.*.') ? $rule : ['sometimes', ...(array) $rule];
        }

        return $rules;
    }
}
