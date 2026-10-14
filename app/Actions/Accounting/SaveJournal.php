<?php

namespace App\Actions\Accounting;

use App\Enums\AccountType;
use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveJournal
{
    /**
     * Create or update a journal.
     *
     * Cash and bank journals need an asset account as default account and other journals none, checked against the
     * type after the update. Journals in use cannot change their type.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes, ?Journal $journal = null): Journal
    {
        return DB::transaction(function () use ($team, $attributes, $journal) {
            $journal ??= new Journal(['team_id' => $team->id]);
            $journal->fill($attributes);

            if ($journal->exists && $journal->isDirty('type') && self::isReferenced($journal)) {
                throw ValidationException::withMessages(['type' => __('The type of journals in use cannot be changed.')]);
            }

            if (! $journal->type->isForPayments() && $journal->default_account_id !== null) {
                throw ValidationException::withMessages(['default_account_id' => __('Only cash and bank journals have a default account.')]);
            }

            if ($journal->type->isForPayments()
                && ChartOfAccount::query()->whereKey($journal->default_account_id)->first()?->account_type !== AccountType::Asset) {
                throw ValidationException::withMessages(['default_account_id' => __('Cash and bank journals need an asset account as default account.')]);
            }

            $journal->save();

            return $journal;
        });
    }

    /**
     * Determine if journal entries, payments or the accounting settings refer to the journal.
     */
    public static function isReferenced(Journal $journal): bool
    {
        return JournalEntry::query()->where('journal_id', $journal->id)->exists()
            || Payment::query()->where('journal_id', $journal->id)->exists()
            || AccountingSetting::query()
                ->where(function ($query) use ($journal) {
                    foreach (AccountingSetting::JOURNAL_COLUMNS as $column) {
                        $query->orWhere($column, $journal->id);
                    }
                })
                ->exists();
    }
}
