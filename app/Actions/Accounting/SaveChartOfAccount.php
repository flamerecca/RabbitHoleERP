<?php

namespace App\Actions\Accounting;

use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntryLine;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveChartOfAccount
{
    /**
     * Create or update an account.
     *
     * An account the accounting settings point to cannot be deactivated, and an account that journal entry lines,
     * the accounting settings or a journal refer to cannot change its type.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes, ?ChartOfAccount $account = null): ChartOfAccount
    {
        return DB::transaction(function () use ($team, $attributes, $account) {
            $account ??= new ChartOfAccount(['team_id' => $team->id, 'is_active' => true]);
            $account->fill($attributes);

            if ($account->exists && $account->isDirty('is_active') && ! $account->is_active && self::isUsedBySettings($account)) {
                throw ValidationException::withMessages(['is_active' => __('Accounts used by the accounting settings cannot be deactivated.')]);
            }

            if ($account->exists && $account->isDirty('account_type') && self::isReferenced($account)) {
                throw ValidationException::withMessages(['account_type' => __('The type of accounts in use cannot be changed.')]);
            }

            $account->save();

            return $account;
        });
    }

    /**
     * Determine if the accounting settings point to the account.
     */
    public static function isUsedBySettings(ChartOfAccount $account): bool
    {
        return AccountingSetting::query()
            ->where(function ($query) use ($account) {
                foreach (AccountingSetting::ACCOUNT_COLUMNS as $column) {
                    $query->orWhere($column, $account->id);
                }
            })
            ->exists();
    }

    /**
     * Determine if journal entry lines, the accounting settings or a journal refer to the account.
     */
    public static function isReferenced(ChartOfAccount $account): bool
    {
        return self::isUsedBySettings($account)
            || JournalEntryLine::query()->where('account_id', $account->id)->exists()
            || Journal::query()->where('default_account_id', $account->id)->exists();
    }
}
