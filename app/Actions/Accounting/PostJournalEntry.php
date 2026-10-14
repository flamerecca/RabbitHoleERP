<?php

namespace App\Actions\Accounting;

use App\Enums\DocumentType;
use App\Enums\JournalEntryStatus;
use App\Enums\PartnerType;
use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class PostJournalEntry
{
    public function __construct(protected DocumentSequence $documentSequence)
    {
    }

    /**
     * Get the accounting settings of the team, which every posting needs.
     */
    public static function settings(Team $team): AccountingSetting
    {
        $settings = AccountingSetting::query()->where('team_id', $team->id)->first();

        abort_if($settings === null, 409, __('The accounting settings of the team are missing.'));

        return $settings;
    }

    /**
     * Post a balanced journal entry for the document header.
     *
     * Amounts are rounded to 4 decimal places and lines without an amount are left out. Lines of receivable and
     * payable accounts stay open for reconciliation with their amount as residual and the entry date as due date,
     * unless `open` is false. Nothing is posted when every line is zero.
     *
     * @param  list<array{account_id: int, debit?: float, credit?: float, partner_type?: PartnerType, partner_id?: int, currency_id?: int, exchange_rate?: float, amount_currency?: float, open?: bool}>  $lines
     *
     * @throws LogicException
     */
    public function handle(Model $reference, Team $team, int $journalId, CarbonInterface $entryDate, User $user, array $lines, ?string $description = null): ?JournalEntry
    {
        $lines = array_values(array_filter(
            array_map(fn (array $line) => [...$line, 'debit' => round($line['debit'] ?? 0.0, 4), 'credit' => round($line['credit'] ?? 0.0, 4)], $lines),
            fn (array $line) => $line['debit'] > 0 || $line['credit'] > 0,
        ));

        if ($lines === []) {
            return null;
        }

        $totalDebit = round(array_sum(array_column($lines, 'debit')), 4);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 4);

        if ($totalDebit !== $totalCredit) {
            throw new LogicException("The journal entry is not balanced: debit [{$totalDebit}], credit [{$totalCredit}].");
        }

        return DB::transaction(function () use ($reference, $team, $journalId, $entryDate, $user, $lines, $description) {
            $accounts = ChartOfAccount::query()
                ->where('team_id', $team->id)
                ->whereKey(array_column($lines, 'account_id'))
                ->get()
                ->keyBy('id');

            abort_if(
                $accounts->count() !== count(array_unique(array_column($lines, 'account_id'))),
                409,
                __('The accounting settings of the team are missing.'),
            );

            $entry = JournalEntry::create([
                'team_id' => $team->id,
                'journal_id' => $journalId,
                'entry_no' => $this->documentSequence->next($team, DocumentType::JournalEntry, $entryDate),
                'entry_date' => $entryDate,
                'reference_type' => $reference->getMorphClass(),
                'reference_id' => $reference->getKey(),
                'status' => JournalEntryStatus::Posted,
                'description' => $description,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $isOpen = $accounts[$line['account_id']]->account_type->isReconcilable() && ($line['open'] ?? true);

                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'partner_type' => $line['partner_type'] ?? null,
                    'partner_id' => $line['partner_id'] ?? null,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'currency_id' => $line['currency_id'] ?? null,
                    'exchange_rate' => $line['exchange_rate'] ?? null,
                    'amount_currency' => isset($line['amount_currency']) ? round($line['amount_currency'], 4) : null,
                    'due_date' => isset($line['partner_type']) ? $entryDate : null,
                    'amount_residual' => $isOpen ? $line['debit'] + $line['credit'] : 0,
                ]);
            }

            return $entry->load('lines');
        });
    }
}
