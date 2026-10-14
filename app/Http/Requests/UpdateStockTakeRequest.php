<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockTakeRequest extends FormRequest
{
    /**
     * Get the validation rules for updating the counts of a draft stock take.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'taken_date' => ['sometimes', 'date_format:Y-m-d'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.stock_lot_id' => ['nullable', 'integer', Rule::exists('stock_lots', 'id')->where('team_id', $team->id)],
        ];
    }
}
