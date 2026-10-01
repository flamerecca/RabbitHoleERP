<?php

namespace App\Actions\Inventory;

use App\Enums\CostMethod;
use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\StockBalance;
use App\Models\StockCostLayer;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReconcileStockTake
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected UnreserveSalesOrderStock $unreserveSalesOrderStock,
    ) {}

    /**
     * Confirm a draft stock take: bring the stock on hand to the counted quantities, measured against the stock
     * at confirmation time, and release reservations that a stock loss no longer covers.
     *
     * @throws ValidationException
     */
    public function handle(StockTake $document, User $user): StockTake
    {
        return DB::transaction(function () use ($document, $user) {
            $document = StockTake::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft stock takes can be confirmed.'));

            $document->load('items.product.category');

            foreach ($document->items as $index => $line) {
                if ($line->counted_quantity === null) {
                    throw ValidationException::withMessages(["items.{$index}.counted_quantity" => __('Every line needs a counted quantity before the stock take is confirmed.')]);
                }
            }

            foreach ($document->items as $line) {
                $this->reconcileLine($document, $line, $user);
            }

            $document->update(['status' => DocumentStatus::Confirmed]);

            return $document->fresh('items');
        });
    }

    /**
     * Refresh the system quantity of one line, of its lot when it counts a lot, and record the adjustment for its difference.
     */
    protected function reconcileLine(StockTake $document, StockTakeItem $line, User $user): void
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $document->warehouse_id)
            ->where('product_id', $line->product_id)
            ->lockForUpdate()
            ->first();
        $systemQuantity = $line->stock_lot_id === null
            ? ($balance === null ? 0.0 : (float) $balance->quantity_on_hand)
            : LotQuantities::onHand($document->warehouse_id, $line->stock_lot_id);
        $difference = round((float) $line->counted_quantity - $systemQuantity, 4);

        $line->update(['system_quantity' => $systemQuantity, 'difference' => $difference]);

        if ($difference == 0) {
            return;
        }

        $this->recordStockMovement->handle(
            reference: $line,
            teamId: $document->team_id,
            warehouseId: $document->warehouse_id,
            product: $line->product,
            type: $difference > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut,
            quantity: $difference,
            user: $user,
            unitCostInBaseCurrency: $difference > 0 ? $this->gainUnitCost($document, $line, $balance) : null,
            stockLotId: $line->stock_lot_id,
        );

        if ($difference < 0) {
            $this->unreserveSalesOrderStock->releaseExcess($document->warehouse_id, $line->product_id);
        }
    }

    /**
     * Get the unit cost of a stock gain: the average cost, or for FIFO products the weighted cost of the
     * remaining layers, else the latest layer's cost, else zero.
     */
    protected function gainUnitCost(StockTake $document, StockTakeItem $line, ?StockBalance $balance): float
    {
        if ($line->product->costMethod() !== CostMethod::Fifo) {
            return $balance === null ? 0.0 : (float) $balance->average_cost;
        }

        $layers = StockCostLayer::query()->where('warehouse_id', $document->warehouse_id)->where('product_id', $line->product_id);
        $remaining = (clone $layers)->where('remaining_quantity', '>', 0)->get(['remaining_quantity', 'unit_cost']);
        $remainingQuantity = (float) $remaining->sum('remaining_quantity');

        if ($remainingQuantity > 0) {
            return round($remaining->sum(fn (StockCostLayer $layer) => (float) $layer->remaining_quantity * (float) $layer->unit_cost) / $remainingQuantity, 4);
        }

        return (float) ($layers->orderByDesc('id')->value('unit_cost') ?? 0);
    }
}
