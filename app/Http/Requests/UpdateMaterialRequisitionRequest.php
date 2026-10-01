<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequisitionRequest extends FormRequest
{
    /**
     * Get the validation rules for updating a draft material requisition.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'requisition_date' => ['sometimes', 'date_format:Y-m-d'],
            'purpose' => ['sometimes', 'string', 'max:255'],
            'requested_by' => ['sometimes', 'integer', Rule::exists('team_members', 'user_id')->where('team_id', $team->id)],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
