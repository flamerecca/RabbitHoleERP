<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSupplierRequest extends FormRequest
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
            'code' => [$required, 'string', 'max:50', Rule::unique('suppliers')->where('team_id', $team->id)->ignore($this->route('supplier'))],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'currency_id' => [$required, 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'payment_terms' => ['nullable', 'string', 'max:255'],
        ];
    }
}
