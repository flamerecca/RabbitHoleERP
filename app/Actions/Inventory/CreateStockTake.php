<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLot;
use App\Models\StockTake;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStockTake
{
    public function __construct(protected DocumentSequence $documentSequence) {}

    /**
     * Create a draft stock take with a snapshot of the stock on hand of each counted product.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, User $user, array $attributes): StockTake
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $document = StockTake::create([
                'team_id' => $team->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'take_no' => $this->documentSequence->next($team, DocumentType::StockTake, CarbonImmutable::parse($attributes['taken_date'])),
                'status' => DocumentStatus::Draft,
                'taken_date' => $attributes['taken_date'],
                'created_by' => $user->id,
            ]);

            static::replaceLines($document, $attributes['items']);

            return $document->fresh('items');
        });
    }

    /**
     * Replace the lines of the stock take, one per product and lot, with the current stock on hand as system quantity.
     *
     * @param  list<array<string, mixed>>  $lines
     *
     * @throws ValidationException
     */
    public static function replaceLines(StockTake $document, array $lines): void
    {
        $products = Product::query()->whereIn('id', array_map(fn (array $line) => (int) $line['product_id'], $lines))->get()->keyBy('id');
        $counted = [];

        foreach ($lines as $index => $line) {
            $product = $products->get((int) $line['product_id']);
            $stockLotId = isset($line['stock_lot_id']) ? (int) $line['stock_lot_id'] : null;

            if ($product?->isLotTracked() && $stockLotId === null) {
                throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('A lot is required for products tracked by lot.')]);
            }

            if ($stockLotId !== null && ! StockLot::query()->whereKey($stockLotId)->where('product_id', $product?->id)->exists()) {
                throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('The lot must belong to the product of the line.')]);
            }

            $key = $line['product_id'].'-'.$stockLotId;

            if (isset($counted[$key])) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => __('Each product can only be counted once per stock take.')]);
            }

            $counted[$key] = true;
        }

        $document->items()->get()->each->delete();

        foreach ($lines as $line) {
            $stockLotId = isset($line['stock_lot_id']) ? (int) $line['stock_lot_id'] : null;
            $systemQuantity = static::systemQuantity($document->warehouse_id, (int) $line['product_id'], $stockLotId);
            $countedQuantity = $line['counted_quantity'] ?? null;

            $document->items()->create([
                'product_id' => $line['product_id'],
                'stock_lot_id' => $stockLotId,
                'system_quantity' => $systemQuantity,
                'counted_quantity' => $countedQuantity,
                'difference' => $countedQuantity === null ? null : round((float) $countedQuantity - $systemQuantity, 4),
            ]);
        }
    }

    /**
     * Get the stock on hand of the product, or of its lot when the line counts a lot.
     */
    public static function systemQuantity(int $warehouseId, int $productId, ?int $stockLotId): float
    {
        if ($stockLotId !== null) {
            return LotQuantities::onHand($warehouseId, $stockLotId);
        }

        return (float) StockBalance::query()->where('warehouse_id', $warehouseId)->where('product_id', $productId)->value('quantity_on_hand');
    }
}
