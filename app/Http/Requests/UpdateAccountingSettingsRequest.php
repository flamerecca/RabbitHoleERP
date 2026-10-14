<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Enums\JournalType;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateAccountingSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules for replacing the accounting settings of the team.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        $account = fn (?AccountType $type = null): array => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->when($type !== null, fn (Exists $rule) => $rule->where('account_type', $type?->value))];
        $journal = fn (JournalType $type): array => ['required', 'integer', Rule::exists('journals', 'id')
            ->where('team_id', $team->id)
            ->where('type', $type->value)];

        return [
            'inventory_account_id' => $account(),
            'receivable_account_id' => $account(AccountType::Receivable),
            'payable_account_id' => $account(AccountType::Payable),
            'sales_revenue_account_id' => $account(),
            'cost_of_goods_sold_account_id' => $account(),
            'research_expense_account_id' => $account(),
            'inventory_adjustment_account_id' => $account(),
            'sales_journal_id' => $journal(JournalType::Sales),
            'purchase_journal_id' => $journal(JournalType::Purchase),
            'general_journal_id' => $journal(JournalType::General),
        ];
    }
}
