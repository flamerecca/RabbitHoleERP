<?php

namespace App\Actions\Accounting;

use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class DeleteChartOfAccount
{
    /**
     * Delete the account unless journal entry lines, the accounting settings or a journal refer to it.
     */
    public function handle(ChartOfAccount $account): void
    {
        DB::transaction(function () use ($account) {
            abort_if(SaveChartOfAccount::isReferenced($account), 409, __('Accounts in use cannot be deleted, deactivate them instead.'));

            $account->delete();
        });
    }
}
