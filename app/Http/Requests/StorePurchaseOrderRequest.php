<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesOrderLines;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseOrderRequest extends FormRequest
{
    use ValidatesOrderLines;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->team();

        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'order_date' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            ...$this->orderLineRules($team, 'items.*.'),
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateOrderLineUnits($validator, (array) $this->input('items', []), 'items.')];
    }

    /**
     * Get the team of the route.
     */
    protected function team(): Team
    {
        /** @var Team $team */
        $team = $this->route('team');

        return $team;
    }
}
