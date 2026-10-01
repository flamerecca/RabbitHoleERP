<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseTransferRequest extends FormRequest
{
    /**
     * Get the validation rules for updating a draft warehouse transfer.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'transfer_date' => ['sometimes', 'date_format:Y-m-d'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
