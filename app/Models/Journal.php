<?php

namespace App\Models;

use App\Enums\JournalType;
use Database\Factories\JournalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 傳票簿，對應 Odoo 的 account.journal，cash、bank 類型以 default_account_id 指定收付款入帳科目。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $code
 * @property string $name
 * @property JournalType $type
 * @property int|null $default_account_id
 * @property-read ChartOfAccount|null $defaultAccount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'code', 'name', 'type', 'default_account_id'])]
class Journal extends Model
{
    /** @use HasFactory<JournalFactory> */
    use HasFactory;

    /**
     * Get the team of the journal.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the cash or bank account payments of the journal are posted to.
     *
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function defaultAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_account_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => JournalType::class,
        ];
    }
}
