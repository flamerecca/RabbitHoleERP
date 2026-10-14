<?php

namespace App\Actions\Accounting;

use App\Models\Journal;
use Illuminate\Support\Facades\DB;

class DeleteJournal
{
    /**
     * Delete the journal unless journal entries, payments or the accounting settings refer to it.
     */
    public function handle(Journal $journal): void
    {
        DB::transaction(function () use ($journal) {
            abort_if(SaveJournal::isReferenced($journal), 409, __('Journals in use cannot be deleted.'));

            $journal->delete();
        });
    }
}
