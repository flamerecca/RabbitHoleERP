<?php

namespace App\Models;

use App\Enums\PosPaymentMethodType;
use Database\Factories\PosPaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * POS 付款方式，綁定現金或銀行傳票簿。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $name
 * @property PosPaymentMethodType $type
 * @property int $journal_id
 * @property-read Journal $journal
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'name', 'type', 'journal_id', 'is_active'])]
class PosPaymentMethod extends Model
{
    /** @use HasFactory<PosPaymentMethodFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the team of the payment method.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the journal the payments of this method are posted to.
     *
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the configs that accept the payment method.
     *
     * @return BelongsToMany<PosConfig, $this>
     */
    public function configs(): BelongsToMany
    {
        return $this->belongsToMany(PosConfig::class, 'pos_config_payment_method', 'pos_payment_method_id', 'pos_config_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PosPaymentMethodType::class,
            'is_active' => 'boolean',
        ];
    }
}
