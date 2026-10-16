<?php

namespace App\Models;

use Database\Factories\PosConfigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * POS 門市，綁定倉庫與銷貨傳票簿，並指定可使用的付款方式。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property string $code
 * @property string $name
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $sales_journal_id
 * @property-read Journal $salesJournal
 * @property string|null $receipt_header
 * @property string|null $receipt_footer
 * @property bool $cash_control
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'code', 'name', 'warehouse_id', 'sales_journal_id', 'receipt_header', 'receipt_footer', 'cash_control', 'is_active'])]
class PosConfig extends Model
{
    /** @use HasFactory<PosConfigFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cash_control' => true,
        'is_active' => true,
    ];

    /**
     * Get the team of the config.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse the sales of the config are taken from.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the sales journal the closing entry of the config is posted to.
     *
     * @return BelongsTo<Journal, $this>
     */
    public function salesJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'sales_journal_id');
    }

    /**
     * Get the payment methods the config accepts.
     *
     * @return BelongsToMany<PosPaymentMethod, $this>
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PosPaymentMethod::class, 'pos_config_payment_method', 'pos_config_id', 'pos_payment_method_id');
    }

    /**
     * Get the sessions of the config.
     *
     * @return HasMany<PosSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cash_control' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
