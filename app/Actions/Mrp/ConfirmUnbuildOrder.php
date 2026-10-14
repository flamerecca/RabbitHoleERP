<?php

namespace App\Actions\Mrp;

use App\Actions\Inventory\AllocateStockLots;
use App\Actions\Inventory\EnsureStockAvailable;
use App\Actions\Inventory\RecordStockMovement;
use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\StockBalance;
use App\Models\StockLot;
use App\Models\UnbuildOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmUnbuildOrder
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected EnsureStockAvailable $ensureStockAvailable,
        protected AllocateStockLots $allocateStockLots,
    ) {
    }

    /**
     * Confirm a draft unbuild order: take the product out, oldest lots first, and put the components in at its cost.
     *
     * The cost of the product is split over the components by quantity times current average cost, or by quantity
     * when no component has an average cost. The component with the largest weight takes the rounding remainder, so the value is conserved and no share turns negative.
     */
    public function handle(UnbuildOrder $order, User $user): UnbuildOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = UnbuildOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== DocumentStatus::Draft, 409, __('Only draft unbuild orders can be confirmed.'));

            $order->load(['product', 'items' => fn ($query) => $query->orderBy('id'), 'items.component']);

            $this->ensureStockAvailable->handle(
                $order->warehouse_id,
                [$order->product_id => (float) $order->quantity],
                collect([$order->product_id => $order->product]),
            );

            $totalCost = 0.0;

            foreach ($this->allocateStockLots->handle($order->warehouse_id, $order->product, (float) $order->quantity) as $allocation) {
                $movement = $this->recordStockMovement->handle(
                    reference: $order,
                    teamId: $order->team_id,
                    warehouseId: $order->warehouse_id,
                    product: $order->product,
                    type: StockMovementType::UnbuildOut,
                    quantity: -$allocation['quantity'],
                    user: $user,
                    stockLotId: $allocation['stock_lot_id'],
                );

                $totalCost += abs((float) $movement->total_cost);
            }

            $averageCosts = StockBalance::query()
                ->where('warehouse_id', $order->warehouse_id)
                ->whereIn('product_id', $order->items->pluck('component_product_id'))
                ->pluck('average_cost', 'product_id');

            $weights = $order->items->map(fn ($line) => (float) $line->quantity * (float) ($averageCosts[$line->component_product_id] ?? 0));

            if ($weights->sum() <= 0) {
                $weights = $order->items->map(fn ($line) => (float) $line->quantity);
            }

            $weightSum = (float) $weights->sum();
            $largest = $weights->search($weights->max());
            $shares = $weights->map(fn (float $weight) => round($totalCost * $weight / $weightSum, 4))->all();
            $shares[$largest] = round($totalCost - (array_sum($shares) - $shares[$largest]), 4);

            foreach ($order->items->values() as $index => $line) {
                $share = $shares[$index];
                $quantity = (float) $line->quantity;

                $stockLotId = $line->component->isLotTracked()
                    ? StockLot::query()->firstOrCreate([
                        'team_id' => $order->team_id,
                        'product_id' => $line->component_product_id,
                        'lot_no' => $order->unbuild_no,
                    ])->id
                    : null;

                $this->recordStockMovement->handle(
                    reference: $line,
                    teamId: $order->team_id,
                    warehouseId: $order->warehouse_id,
                    product: $line->component,
                    type: StockMovementType::UnbuildIn,
                    quantity: $quantity,
                    user: $user,
                    unitCostInBaseCurrency: $share / $quantity,
                    incomingLayers: $this->costLayers($share, $quantity),
                    stockLotId: $stockLotId,
                );
            }

            $order->update(['status' => DocumentStatus::Confirmed, 'unbuilt_at' => now()]);

            return $order->fresh('items');
        });
    }

    /**
     * Split the share into cost layers whose values add up to it exactly, as layer unit costs hold four decimals.
     *
     * The unit cost is cut down to four decimals for all but one unit, which takes the remainder.
     *
     * @return list<array{quantity: float, unit_cost: float}>
     */
    protected function costLayers(float $share, float $quantity): array
    {
        if ($quantity <= 1) {
            return [['quantity' => $quantity, 'unit_cost' => $share / $quantity]];
        }

        $unitCost = floor($share / $quantity * 10000) / 10000;
        $remainder = round($share - round(($quantity - 1) * $unitCost, 4), 4);

        return [
            ['quantity' => round($quantity - 1, 4), 'unit_cost' => $unitCost],
            ['quantity' => 1.0, 'unit_cost' => $remainder],
        ];
    }
}
