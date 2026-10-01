<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 倉庫主檔。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property bool $is_default
 * @property string|null $manager_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'name', 'code', 'address', 'is_default', 'manager_email'])]
class Warehouse extends Model
{
    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
    ];

    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    /**
     * Get the team of the warehouse.
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
            'is_default' => 'boolean',
        ];
    }
}
