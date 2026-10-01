<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
{
    /**
     * Get the validation rules for creating a purchase return.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'goods_receipt_id' => ['required', 'integer', Rule::exists('goods_receipts', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.stock_lot_id' => ['nullable', 'integer', Rule::exists('stock_lots', 'id')->where('team_id', $team->id)],
            'items.*.is_whole_lot' => ['sometimes', 'boolean'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
