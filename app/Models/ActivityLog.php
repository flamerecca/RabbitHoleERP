<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 操作紀錄。
 *
 * @property int $id
 * @property int $c1
 * @property-read Team $team
 * @property int|null $c2
 * @property-read User|null $causer
 * @property ActivityEvent $c3
 * @property string $c4
 * @property int $c5
 * @property string $c6
 * @property int $c7
 * @property string|null $c8
 * @property array<string, array{old: mixed, new: mixed}> $c9
 * @property Carbon|null $c10
 */
#[Fillable(['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public const CREATED_AT = 'c10';

    public const UPDATED_AT = null;

    /**
     * Get the team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'c1');
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'c2');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'c3' => ActivityEvent::class,
            'c9' => 'array',
            'c10' => 'datetime',
        ];
    }
}
