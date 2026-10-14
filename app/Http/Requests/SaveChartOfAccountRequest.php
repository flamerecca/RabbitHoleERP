<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveChartOfAccountRequest extends FormRequest
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
            'code' => [$required, 'string', 'max:20', Rule::unique('chart_of_accounts')->where('team_id', $team->id)->ignore($this->route('chartOfAccount'))],
            'name' => [$required, 'string', 'max:255'],
            'account_type' => [$required, Rule::enum(AccountType::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
