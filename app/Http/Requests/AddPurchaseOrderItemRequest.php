<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesOrderLines;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddPurchaseOrderItemRequest extends FormRequest
{
    use ValidatesOrderLines;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return $this->orderLineRules($team, '');
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateOrderLineUnits($validator, ['line' => $this->all()], '')];
    }
}
