<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsignmentHoldRequest extends FormRequest
{
    /**
     * Get the validation rules for holding the goods of a shipment.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'shipment_id' => ['required', 'integer', Rule::exists('shipments', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'held_from' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
