<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * 計量單位，ratio 為一個此單位等於多少個基準單位。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $category_id
 * @property-read UnitCategory $category
 * @property string $name
 * @property string $code
 * @property string $ratio
 * @property bool $is_reference
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'category_id', 'name', 'code', 'ratio', 'is_reference', 'is_active'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * Get the team of the unit.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the category of the unit.
     *
     * @return BelongsTo<UnitCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(UnitCategory::class, 'category_id');
    }

    /**
     * Convert a quantity measured in this unit into the given unit of the same category.
     *
     * @throws InvalidArgumentException
     */
    public function convertQuantity(float|int|string $quantity, Unit $to): float
    {
        $this->ensureSameCategory($to);

        return round((float) $quantity * (float) $this->ratio / (float) $to->ratio, 4);
    }

    /**
     * Convert a price per this unit into a price per the given unit of the same category.
     *
     * @throws InvalidArgumentException
     */
    public function convertPrice(float|int|string $price, Unit $to): float
    {
        $this->ensureSameCategory($to);

        return round((float) $price * (float) $to->ratio / (float) $this->ratio, 4);
    }

    /**
     * Ensure the given unit belongs to the same unit category.
     *
     * @throws InvalidArgumentException
     */
    protected function ensureSameCategory(Unit $other): void
    {
        if ($this->category_id !== $other->category_id) {
            throw new InvalidArgumentException("Unit [{$this->code}] cannot be converted to unit [{$other->code}] of another category.");
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ratio' => 'decimal:8',
            'is_reference' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
