<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 單號規則在某個重置期間的流水號計數器。
 *
 * @property int $id
 * @property int $document_sequence_id
 * @property string $period_key
 * @property int $last_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DocumentSequence $documentSequence
 */
#[Fillable(['document_sequence_id', 'period_key', 'last_number'])]
class DocumentSequenceCounter extends Model
{
    /**
     * Get the rule of the counter.
     *
     * @return BelongsTo<DocumentSequence, $this>
     */
    public function documentSequence(): BelongsTo
    {
        return $this->belongsTo(DocumentSequence::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }
}
