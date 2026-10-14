<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWarehouseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request, every field being optional on a partial update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'code' => [$required, 'string', 'max:50', Rule::unique('warehouses')->where('team_id', $team->id)->ignore($this->route('warehouse'))],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['sometimes', 'boolean'],
            'manager_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
