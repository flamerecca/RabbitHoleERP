<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * 分錄主檔，對應 Odoo 的 account.move，由業務單據與收付款單確認時自動產生。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $journal_id
 * @property-read Journal $journal
 * @property string $entry_no
 * @property Carbon $entry_date
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property-read Model|null $reference
 * @property JournalEntryStatus $status
 * @property string|null $description
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, JournalEntryLine> $lines
 */
#[Fillable(['team_id', 'journal_id', 'entry_no', 'entry_date', 'reference_type', 'reference_id', 'status', 'description', 'created_by'])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    /**
     * Get the team of the journal entry.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the journal of the journal entry.
     *
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the document header that posted the journal entry.
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the debit and credit lines of the journal entry, in the order they were posted.
     *
     * @return HasMany<JournalEntryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'status' => JournalEntryStatus::class,
        ];
    }
}
