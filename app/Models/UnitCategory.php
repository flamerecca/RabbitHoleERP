<?php

namespace App\Models;

use Database\Factories\UnitCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 計量單位分類，同一分類內的單位才能互相換算。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Unit> $units
 */
#[Fillable(['team_id', 'name'])]
class UnitCategory extends Model
{
    /** @use HasFactory<UnitCategoryFactory> */
    use HasFactory;

    /**
     * Get the team of the unit category.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the line items of the unit category.
     *
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'category_id');
    }
}
