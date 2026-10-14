<?php

namespace App\Models;

use Database\Factories\StockLotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 批號，同一產品在 Team 內唯一。
 *
 * @property int $id
 * @property int $team_id
 * @property-read Team $team
 * @property int $product_id
 * @property-read Product $product
 * @property string $lot_no
 * @property-read Collection<int, StockLotBalance> $balances
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'product_id', 'lot_no'])]
class StockLot extends Model
{
    /** @use HasFactory<StockLotFactory> */
    use HasFactory;

    /**
     * Get the team of the lot.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the product of the lot.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock of the lot in each warehouse.
     *
     * @return HasMany<StockLotBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(StockLotBalance::class);
    }
}
