<?php

namespace App\Actions\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalType;
use App\Models\AccountingSetting;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class SeedDefaultAccounting
{
    /**
     * The default chart of accounts, with the settings column each account fills.
     *
     * @var list<array{code: string, name: string, account_type: AccountType, setting: string|null}>
     */
    protected const ACCOUNTS = [
        ['code' => '1100', 'name' => '現金', 'account_type' => AccountType::Asset, 'setting' => null],
        ['code' => '1110', 'name' => '銀行存款', 'account_type' => AccountType::Asset, 'setting' => null],
        ['code' => '1150', 'name' => '應收帳款', 'account_type' => AccountType::Receivable, 'setting' => 'receivable_account_id'],
        ['code' => '1300', 'name' => '存貨', 'account_type' => AccountType::Asset, 'setting' => 'inventory_account_id'],
        ['code' => '2150', 'name' => '應付帳款', 'account_type' => AccountType::Payable, 'setting' => 'payable_account_id'],
        ['code' => '2200', 'name' => '銷項稅額', 'account_type' => AccountType::Liability, 'setting' => 'output_tax_account_id'],
        ['code' => '3100', 'name' => '股本', 'account_type' => AccountType::Equity, 'setting' => null],
        ['code' => '4100', 'name' => '銷貨收入', 'account_type' => AccountType::Income, 'setting' => 'sales_revenue_account_id'],
        ['code' => '5100', 'name' => '銷貨成本', 'account_type' => AccountType::Expense, 'setting' => 'cost_of_goods_sold_account_id'],
        ['code' => '6100', 'name' => '研究發展費用', 'account_type' => AccountType::Expense, 'setting' => 'research_expense_account_id'],
        ['code' => '6200', 'name' => '存貨盤點損益', 'account_type' => AccountType::Expense, 'setting' => 'inventory_adjustment_account_id'],
    ];

    /**
     * The default journals, with the settings column or the default account code of each.
     *
     * @var list<array{code: string, name: string, type: JournalType, setting: string|null, default_account: string|null}>
     */
    protected const JOURNALS = [
        ['code' => 'SAL', 'name' => '銷貨傳票簿', 'type' => JournalType::Sales, 'setting' => 'sales_journal_id', 'default_account' => null],
        ['code' => 'PUR', 'name' => '進貨傳票簿', 'type' => JournalType::Purchase, 'setting' => 'purchase_journal_id', 'default_account' => null],
        ['code' => 'GEN', 'name' => '一般傳票簿', 'type' => JournalType::General, 'setting' => 'general_journal_id', 'default_account' => null],
        ['code' => 'CSH', 'name' => '現金傳票簿', 'type' => JournalType::Cash, 'setting' => null, 'default_account' => '1100'],
        ['code' => 'BNK', 'name' => '銀行傳票簿', 'type' => JournalType::Bank, 'setting' => null, 'default_account' => '1110'],
    ];

    /**
     * Create the default chart of accounts, journals and accounting settings of a team that has none yet.
     */
    public function handle(Team $team): void
    {
        DB::transaction(function () use ($team) {
            if (AccountingSetting::query()->where('team_id', $team->id)->exists()) {
                return;
            }

            $settings = ['team_id' => $team->id];
            $accountIds = [];

            foreach (self::ACCOUNTS as $account) {
                $accountIds[$account['code']] = $team->chartOfAccounts()->create([
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'account_type' => $account['account_type'],
                    'is_active' => true,
                ])->id;

                if ($account['setting'] !== null) {
                    $settings[$account['setting']] = $accountIds[$account['code']];
                }
            }

            foreach (self::JOURNALS as $journal) {
                $journalId = $team->journals()->create([
                    'code' => $journal['code'],
                    'name' => $journal['name'],
                    'type' => $journal['type'],
                    'default_account_id' => $journal['default_account'] === null ? null : $accountIds[$journal['default_account']],
                ])->id;

                if ($journal['setting'] !== null) {
                    $settings[$journal['setting']] = $journalId;
                }
            }

            AccountingSetting::create($settings);
        });
    }
}
