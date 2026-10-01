<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoodsReceiptRequest extends FormRequest
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

            'warehouse_id' => ['sometimes', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'received_date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.lot_no' => ['nullable', 'string', 'max:50'],
        ];
    }
}
