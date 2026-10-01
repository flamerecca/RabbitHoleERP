<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'sales_order_id' => ['required', 'integer', Rule::exists('sales_orders', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'shipped_date' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.stock_lot_id' => ['nullable', 'integer', Rule::exists('stock_lots', 'id')->where('team_id', $team->id)],

        ];
    }
}
