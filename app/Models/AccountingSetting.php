<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Team 的會計設定，指定自動過帳使用的預設科目與傳票簿，建立 Team 時由 SeedDefaultAccounting 一併建立，因此沒有 Factory。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $inventory_account_id
 * @property int $receivable_account_id
 * @property int $payable_account_id
 * @property int $sales_revenue_account_id
 * @property int $cost_of_goods_sold_account_id
 * @property int $research_expense_account_id
 * @property int $inventory_adjustment_account_id
 * @property int $sales_journal_id
 * @property int $purchase_journal_id
 * @property int $general_journal_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'team_id', 'inventory_account_id', 'receivable_account_id', 'payable_account_id', 'sales_revenue_account_id',
    'cost_of_goods_sold_account_id', 'research_expense_account_id', 'inventory_adjustment_account_id',
    'sales_journal_id', 'purchase_journal_id', 'general_journal_id',
])]
class AccountingSetting extends Model
{
    /**
     * The setting columns that point to accounts.
     *
     * @var list<string>
     */
    public const ACCOUNT_COLUMNS = [
        'inventory_account_id', 'receivable_account_id', 'payable_account_id', 'sales_revenue_account_id',
        'cost_of_goods_sold_account_id', 'research_expense_account_id', 'inventory_adjustment_account_id',
    ];

    /**
     * The setting columns that point to journals.
     *
     * @var list<string>
     */
    public const JOURNAL_COLUMNS = ['sales_journal_id', 'purchase_journal_id', 'general_journal_id'];

    /**
     * Get the team of the settings.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
