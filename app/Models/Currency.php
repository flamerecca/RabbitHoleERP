<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Team 可用幣別與匯率。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $code
 * @property string $name
 * @property string|null $symbol
 * @property string $exchange_rate_to_base
 * @property bool $is_base
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'code', 'name', 'symbol', 'exchange_rate_to_base', 'is_base'])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * Get the team of the currency.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate_to_base' => 'decimal:8',
            'is_base' => 'boolean',
        ];
    }
}
