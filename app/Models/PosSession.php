<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\PosSessionStatus;
use Database\Factories\PosSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * POS 班次，記錄一個門市一段營業時間的開班與關班，以及現金盤點結果。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $pos_config_id
 * @property-read PosConfig $posConfig
 * @property string $session_no
 * @property PosSessionStatus $status
 * @property int $opened_by
 * @property-read User $openedBy
 * @property Carbon $opened_at
 * @property string|null $opening_cash
 * @property int|null $closed_by
 * @property-read User|null $closedBy
 * @property Carbon|null $closed_at
 * @property string|null $closing_cash_counted
 * @property string|null $closing_cash_expected
 * @property string|null $cash_difference
 * @property string|null $closing_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'team_id', 'pos_config_id', 'session_no', 'status', 'opened_by', 'opened_at', 'opening_cash', 'closed_by', 'closed_at',
    'closing_cash_counted', 'closing_cash_expected', 'cash_difference', 'closing_note',
])]
class PosSession extends Model implements HasActivityLog
{
    /** @use HasFactory<PosSessionFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * Get the number that identifies the session in the activity log.
     */
    public function activityLabel(): ?string
    {
        return $this->session_no;
    }

    /**
     * Get the team of the session.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the POS store the session belongs to.
     *
     * @return BelongsTo<PosConfig, $this>
     */
    public function posConfig(): BelongsTo
    {
        return $this->belongsTo(PosConfig::class);
    }

    /**
     * Get the user who opened the session.
     *
     * @return BelongsTo<User, $this>
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Get the user who closed the session.
     *
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PosSessionStatus::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:4',
            'closing_cash_counted' => 'decimal:4',
            'closing_cash_expected' => 'decimal:4',
            'cash_difference' => 'decimal:4',
        ];
    }
}
