<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Contracts\HasActivityLog;
use App\Enums\PosOrderStatus;
use Database\Factories\PosOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * POS 訂單，一次結帳，價格含稅，付款後鎖定。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $pos_session_id
 * @property-read PosSession $posSession
 * @property string $order_no
 * @property PosOrderStatus $status
 * @property int|null $customer_id
 * @property-read Customer|null $customer
 * @property string $amount_total
 * @property string $amount_tax
 * @property string $amount_paid
 * @property string $amount_change
 * @property int $created_by
 * @property-read User $createdBy
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PosOrderLine> $lines
 * @property-read Collection<int, PosPayment> $payments
 */
#[Fillable([
    'team_id', 'pos_session_id', 'order_no', 'status', 'customer_id', 'amount_total', 'amount_tax', 'amount_paid',
    'amount_change', 'created_by', 'paid_at',
])]
class PosOrder extends Model implements HasActivityLog
{
    /** @use HasFactory<PosOrderFactory> */
    use HasFactory;
    use RecordsActivity;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'amount_total' => 0,
        'amount_tax' => 0,
        'amount_paid' => 0,
        'amount_change' => 0,
    ];

    /**
     * Get the calculated amounts left out of the activity log, the status and payment time already tell what happened.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['amount_total', 'amount_tax', 'amount_paid', 'amount_change'];
    }

    /**
     * Get the team of the order.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the session the order was rung up in.
     *
     * @return BelongsTo<PosSession, $this>
     */
    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    /**
     * Get the customer of the order, if the cashier chose one.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the cashier who created the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the lines of the order.
     *
     * @return HasMany<PosOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PosOrderLine::class)->orderBy('id');
    }

    /**
     * Get the payments of the order.
     *
     * @return HasMany<PosPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class)->orderBy('id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PosOrderStatus::class,
            'amount_total' => 'decimal:4',
            'amount_tax' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'amount_change' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }
}
