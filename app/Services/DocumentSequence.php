<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\DocumentSequence as DocumentSequenceRule;
use App\Models\DocumentSequenceCounter;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DocumentSequence
{
    /**
     * Generate the next document number of the team for the document date.
     */
    public function next(Team $team, DocumentType $documentType, CarbonInterface $date): string
    {
        return DB::transaction(function () use ($team, $documentType, $date) {
            $rule = $this->rule($team, $documentType);
            $periodKey = $rule->reset_period->periodKey($date);

            DocumentSequenceCounter::query()->insertOrIgnore([
                'document_sequence_id' => $rule->id,
                'period_key' => $periodKey,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DocumentSequenceCounter::query()
                ->where('document_sequence_id', $rule->id)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->firstOrFail();

            do {
                $counter->last_number++;
                $number = $rule->format($date, $counter->last_number);
            } while ($this->isTaken($team, $documentType, $number));

            $counter->save();

            return $number;
        });
    }

    /**
     * Get the document number the next document would receive, without consuming it.
     */
    public function preview(Team $team, DocumentType $documentType, CarbonInterface $date): string
    {
        $rule = DocumentSequenceRule::query()
            ->where('team_id', $team->id)
            ->where('document_type', $documentType)
            ->first()
            ?? new DocumentSequenceRule(['team_id' => $team->id, 'document_type' => $documentType, ...DocumentSequenceRule::defaultsFor($documentType)]);

        $lastNumber = $rule->exists
            ? (int) DocumentSequenceCounter::query()
                ->where('document_sequence_id', $rule->id)
                ->where('period_key', $rule->reset_period->periodKey($date))
                ->value('last_number')
            : 0;

        do {
            $lastNumber++;
            $number = $rule->format($date, $lastNumber);
        } while ($this->isTaken($team, $documentType, $number));

        return $number;
    }

    /**
     * Get the rule of the document type, creating it with the defaults on first use.
     */
    protected function rule(Team $team, DocumentType $documentType): DocumentSequenceRule
    {
        return DocumentSequenceRule::query()->firstOrCreate(
            ['team_id' => $team->id, 'document_type' => $documentType],
            DocumentSequenceRule::defaultsFor($documentType),
        );
    }

    /**
     * Determine if the number is already used by a document of the team.
     */
    protected function isTaken(Team $team, DocumentType $documentType, string $number): bool
    {
        $numberColumn = $documentType->numberColumn();

        if ($numberColumn === null) {
            return false;
        }

        [$table, $column] = $numberColumn;

        return DB::table($table)->where('team_id', $team->id)->where($column, $number)->exists();
    }
}
