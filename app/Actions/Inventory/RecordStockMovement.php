<?php

namespace App\Actions\Inventory;

use App\Enums\CostMethod;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockCostLayer;
use App\Models\StockCostLayerConsumption;
use App\Models\StockLot;
use App\Models\StockLotBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\NativeCosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class RecordStockMovement
{
    public function __construct(protected NativeCosting $nativeCosting) {}

    /**
     * Record a stock movement in the product stock unit, update the warehouse balance and value it
     * with the costing method of the product category.
     *
     * A positive quantity increases the balance at the given base currency unit cost, or at the current
     * average cost when none is given. A negative quantity decreases the balance at the average cost or by
     * consuming the oldest FIFO cost layers. The balance may not drop below zero.
     *
     * For FIFO products, a decrease first consumes the layers created by the preferred movements, and an
     * increase may create several layers at once, for example one per layer a transfer consumed.
     *
     * @param  list<int>  $preferredLayerMovementIds  Movements whose cost layers are consumed first.
     * @param  list<array{quantity: float, unit_cost: float}>|null  $incomingLayers  Cost layers to create instead of a single one.
     * @param  int|null  $stockLotId  The lot of the movement, required exactly for products tracked by lot.
     */
    public function handle(
        Model $reference,
        int $teamId,
        int $warehouseId,
        Product $product,
        StockMovementType $type,
        float $quantity,
        User $user,
        ?float $unitCostInBaseCurrency = null,
        array $preferredLayerMovementIds = [],
        ?array $incomingLayers = null,
        ?int $stockLotId = null,
    ): StockMovement {
        if ($product->isLotTracked() !== ($stockLotId !== null)) {
            throw new LogicException("Product [{$product->sku}] needs a lot exactly when it is tracked by lot.");
        }

        return DB::transaction(function () use ($reference, $teamId, $warehouseId, $product, $type, $quantity, $user, $unitCostInBaseCurrency, $preferredLayerMovementIds, $incomingLayers, $stockLotId) {
            $balance = StockBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first()
                ?? StockBalance::create([
                    'team_id' => $teamId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'average_cost' => 0,
                ]);

            $onHand = (float) $balance->quantity_on_hand;
            $averageCost = (float) $balance->average_cost;
            $balanceAfter = round($onHand + $quantity, 4);

            abort_if($balanceAfter < 0, 409, __('Insufficient stock for product [:sku].', ['sku' => $product->sku]));

            if ($stockLotId !== null) {
                $this->updateLotBalance($teamId, $warehouseId, $product, $stockLotId, $quantity);
            }

            $movement = StockMovement::create([
                'team_id' => $teamId,
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'stock_lot_id' => $stockLotId,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $balanceAfter,
                'total_cost' => 0,
                'reference_type' => $reference->getMorphClass(),
                'reference_id' => $reference->getKey(),
                'created_by' => $user->id,
            ]);

            $totalCost = $product->loadMissing('category')->costMethod() === CostMethod::Fifo
                ? $this->valueWithFifo($balance, $movement, $quantity, $unitCostInBaseCurrency ?? $averageCost, $preferredLayerMovementIds, $incomingLayers)
                : $this->valueWithAverageCost($balance, $quantity, $onHand, $averageCost, $unitCostInBaseCurrency);

            $balance->quantity_on_hand = (string) $balanceAfter;
            $balance->save();

            $movement->update(['total_cost' => $totalCost]);

            return $movement;
        });
    }

    /**
     * Add the quantity to the stock of the lot in the warehouse, which may not drop below zero.
     */
    protected function updateLotBalance(int $teamId, int $warehouseId, Product $product, int $stockLotId, float $quantity): void
    {
        $lotBalance = StockLotBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('stock_lot_id', $stockLotId)
            ->lockForUpdate()
            ->first()
            ?? new StockLotBalance([
                'team_id' => $teamId,
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'stock_lot_id' => $stockLotId,
                'quantity_on_hand' => 0,
            ]);

        $lotBalanceAfter = round((float) $lotBalance->quantity_on_hand + $quantity, 4);

        abort_if($lotBalanceAfter < 0, 409, __('Insufficient stock in lot [:lot].', ['lot' => StockLot::query()->whereKey($stockLotId)->value('lot_no')]));

        $lotBalance->quantity_on_hand = (string) $lotBalanceAfter;
        $lotBalance->save();
    }

    /**
     * Value the movement with the moving average cost and update the balance average cost on receipts.
     */
    protected function valueWithAverageCost(StockBalance $balance, float $quantity, float $onHand, float $averageCost, ?float $unitCost): float
    {
        if ($quantity < 0) {
            return round(abs($quantity) * $averageCost, 4);
        }

        if ($unitCost === null) {
            return round($quantity * $averageCost, 4);
        }

        $balance->average_cost = (string) $this->nativeCosting->averageCost($onHand, $averageCost, $quantity, $unitCost);

        return round($quantity * $unitCost, 4);
    }

    /**
     * Value the movement with FIFO cost layers: create layers on receipts, consume the preferred and then the oldest layers on issues.
     *
     * @param  list<int>  $preferredLayerMovementIds
     * @param  list<array{quantity: float, unit_cost: float}>|null  $incomingLayers
     */
    protected function valueWithFifo(StockBalance $balance, StockMovement $movement, float $quantity, float $unitCost, array $preferredLayerMovementIds, ?array $incomingLayers): float
    {
        $totalCost = 0.0;

        if ($quantity > 0) {
            foreach ($incomingLayers ?? [['quantity' => $quantity, 'unit_cost' => $unitCost]] as $incoming) {
                StockCostLayer::create([
                    'team_id' => $movement->team_id,
                    'warehouse_id' => $movement->warehouse_id,
                    'product_id' => $movement->product_id,
                    'stock_movement_id' => $movement->id,
                    'quantity' => $incoming['quantity'],
                    'unit_cost' => $incoming['unit_cost'],
                    'remaining_quantity' => $incoming['quantity'],
                ]);

                $totalCost += round($incoming['quantity'] * $incoming['unit_cost'], 4);
            }

            $totalCost = round($totalCost, 4);
        } elseif ($quantity < 0) {
            [$preferred, $others] = $this->remainingLayers($movement)->lockForUpdate()->get()
                ->partition(fn (StockCostLayer $layer) => in_array($layer->stock_movement_id, $preferredLayerMovementIds, true));
            $layers = $preferred->concat($others)->values();
            $result = $this->nativeCosting->consumeFifo($this->layerValues($layers), abs($quantity));

            foreach ($layers->values() as $index => $layer) {
                $taken = $result['consumed'][$index];

                if ($taken <= 0) {
                    continue;
                }

                $layer->update(['remaining_quantity' => round((float) $layer->remaining_quantity - $taken, 4)]);

                StockCostLayerConsumption::create([
                    'stock_cost_layer_id' => $layer->id,
                    'stock_movement_id' => $movement->id,
                    'quantity' => $taken,
                    'unit_cost' => $layer->unit_cost,
                ]);
            }

            $totalCost = $result['total_cost'];
        }

        $remainingLayers = $this->remainingLayers($movement)->get();
        if ($remainingLayers->isNotEmpty()) {
            $balance->average_cost = (string) $this->nativeCosting->fifoAverageCost($this->layerValues($remainingLayers));
        }

        return $totalCost;
    }

    /**
     * Query the cost layers with remaining quantity in the movement warehouse, oldest first.
     *
     * @return Builder<StockCostLayer>
     */
    protected function remainingLayers(StockMovement $movement): Builder
    {
        return StockCostLayer::query()
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('product_id', $movement->product_id)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('id');
    }

    /**
     * Map cost layers to the values the native library expects.
     *
     * @param  Collection<int, StockCostLayer>  $layers
     * @return list<array{remaining: float, unit_cost: float}>
     */
    protected function layerValues(Collection $layers): array
    {
        return array_values($layers->map(fn (StockCostLayer $layer) => [
            'remaining' => (float) $layer->remaining_quantity,
            'unit_cost' => (float) $layer->unit_cost,
        ])->all());
    }
}
