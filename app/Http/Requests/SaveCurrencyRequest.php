<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCurrencyRequest extends FormRequest
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
            'code' => [$required, 'string', 'max:10', Rule::unique('currencies')->where('team_id', $team->id)->ignore($this->route('currency'))],
            'name' => [$required, 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'exchange_rate_to_base' => [$required, 'numeric', 'gt:0'],
            'is_base' => ['sometimes', 'boolean'],
        ];
    }
}
