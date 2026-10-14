<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\ChartOfAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 會計科目，對應 Odoo 的 account.account。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $code
 * @property string $name
 * @property AccountType $account_type
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'code', 'name', 'account_type', 'is_active'])]
class ChartOfAccount extends Model
{
    /** @use HasFactory<ChartOfAccountFactory> */
    use HasFactory;

    /**
     * Get the team of the account.
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
            'account_type' => AccountType::class,
            'is_active' => 'boolean',
        ];
    }
}
