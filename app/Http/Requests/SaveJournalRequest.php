<?php

namespace App\Http\Requests;

use App\Enums\JournalType;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveJournalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request, every field being optional on a partial update.
     *
     * Whether the default account is required depends on the journal type after the update, so `SaveJournal` checks it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'code' => [$required, 'string', 'max:20', Rule::unique('journals')->where('team_id', $team->id)->ignore($this->route('journal'))],
            'name' => [$required, 'string', 'max:255'],
            'type' => [$required, Rule::enum(JournalType::class)],
            'default_account_id' => ['sometimes', 'nullable', 'integer', Rule::exists('chart_of_accounts', 'id')->where('team_id', $team->id)],
        ];
    }
}
