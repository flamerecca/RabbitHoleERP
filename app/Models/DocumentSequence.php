<?php

namespace App\Models;

use App\Enums\DocumentDateFormat;
use App\Enums\DocumentResetPeriod;
use App\Enums\DocumentType;
use Carbon\CarbonInterface;
use Database\Factories\DocumentSequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 單號規則，每個 Team 的每種單據類型各一筆。
 *
 * @property int $id
 * @property int $team_id
 * @property DocumentType $document_type
 * @property string $prefix
 * @property DocumentDateFormat $date_format
 * @property string $separator
 * @property int $padding
 * @property DocumentResetPeriod $reset_period
 * @property bool $is_customized
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DocumentSequenceCounter> $counters
 */
#[Fillable(['team_id', 'document_type', 'prefix', 'date_format', 'separator', 'padding', 'reset_period', 'is_customized'])]
class DocumentSequence extends Model
{
    /** @use HasFactory<DocumentSequenceFactory> */
    use HasFactory;

    /**
     * Get the default rule attributes of the document type.
     *
     * @return array{prefix: string, date_format: DocumentDateFormat, separator: string, padding: int, reset_period: DocumentResetPeriod}
     */
    public static function defaultsFor(DocumentType $documentType): array
    {
        return [
            'prefix' => $documentType->defaultPrefix(),
            'date_format' => DocumentDateFormat::YearMonth,
            'separator' => '/',
            'padding' => 5,
            'reset_period' => DocumentResetPeriod::Monthly,
        ];
    }

    /**
     * Format a document number with the rule.
     */
    public function format(CarbonInterface $date, int $number): string
    {
        return $this->prefix.$this->date_format->format($date).$this->separator.str_pad((string) $number, $this->padding, '0', STR_PAD_LEFT);
    }

    /**
     * Get the counters of the rule.
     *
     * @return HasMany<DocumentSequenceCounter, $this>
     */
    public function counters(): HasMany
    {
        return $this->hasMany(DocumentSequenceCounter::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'date_format' => DocumentDateFormat::class,
            'reset_period' => DocumentResetPeriod::class,
            'padding' => 'integer',
            'is_customized' => 'boolean',
        ];
    }
}
