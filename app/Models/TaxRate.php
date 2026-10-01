<?php

namespace App\Models;

use Database\Factories\TaxRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Team 可用稅率。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $name
 * @property string $rate
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'name', 'rate', 'is_default'])]
class TaxRate extends Model
{
    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
    ];

    /** @use HasFactory<TaxRateFactory> */
    use HasFactory;

    /**
     * Get the team of the tax rate.
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
            'rate' => 'decimal:4',
            'is_default' => 'boolean',
        ];
    }
}
