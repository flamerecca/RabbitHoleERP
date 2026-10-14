<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * 庫存異動紀錄，reference 以多型關聯指向來源單據。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $warehouse_id
 * @property-read Warehouse $warehouse
 * @property int $product_id
 * @property-read Product $product
 * @property int|null $stock_lot_id
 * @property-read StockLot|null $stockLot
 * @property StockMovementType $type
 * @property string $quantity
 * @property string $balance_after
 * @property string $total_cost
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property-read Model|null $reference
 * @property string|null $note
 * @property int $created_by
 * @property-read User $creator
 * @property Carbon|null $created_at
 */
#[Fillable(['team_id', 'warehouse_id', 'product_id', 'stock_lot_id', 'type', 'quantity', 'balance_after', 'total_cost', 'reference_type', 'reference_id', 'note', 'created_by'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the team of the stock movement.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the warehouse of the stock movement.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product of the stock movement.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the source document of the movement.
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the creator of the stock movement.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the lot the movement belongs to.
     *
     * @return BelongsTo<StockLot, $this>
     */
    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }
}
