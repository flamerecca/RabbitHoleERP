<?php

namespace App\Actions\Mrp;

use App\Actions\Inventory\AllocateStockLots;
use App\Actions\Inventory\EnsureStockAvailable;
use App\Actions\Inventory\RecordStockMovement;
use App\Enums\ManufacturingOrderStatus;
use App\Enums\StockMovementType;
use App\Models\ManufacturingOrder;
use App\Models\StockLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteManufacturingOrder
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected EnsureStockAvailable $ensureStockAvailable,
        protected AllocateStockLots $allocateStockLots,
        protected SplitCostLayers $splitCostLayers,
    ) {
    }

    /**
     * Complete a confirmed manufacturing order: consume the components, oldest lots first, and receive the product at their cost.
     *
     * Everything runs in one transaction, so a failure leaves the order confirmed and the stock untouched.
     */
    public function handle(ManufacturingOrder $order, User $user): ManufacturingOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = ManufacturingOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== ManufacturingOrderStatus::Confirmed, 409, __('Only confirmed manufacturing orders can be completed.'));

            $order->load(['product', 'items' => fn ($query) => $query->orderBy('id'), 'items.component']);

            $this->ensureStockAvailable->handle(
                $order->warehouse_id,
                $order->items->mapWithKeys(fn ($line) => [$line->component_product_id => (float) $line->quantity])->all(),
                $order->items->pluck('component', 'component_product_id'),
            );

            $totalCost = 0.0;

            foreach ($order->items as $line) {
                foreach ($this->allocateStockLots->handle($order->warehouse_id, $line->component, (float) $line->quantity) as $allocation) {
                    $movement = $this->recordStockMovement->handle(
                        reference: $line,
                        teamId: $order->team_id,
                        warehouseId: $order->warehouse_id,
                        product: $line->component,
                        type: StockMovementType::ProductionConsume,
                        quantity: -$allocation['quantity'],
                        user: $user,
                        stockLotId: $allocation['stock_lot_id'],
                    );

                    $totalCost += abs((float) $movement->total_cost);
                }
            }

            $totalCost = round($totalCost, 4);
            $quantity = (float) $order->quantity;

            $stockLotId = $order->product->isLotTracked()
                ? StockLot::query()->firstOrCreate([
                    'team_id' => $order->team_id,
                    'product_id' => $order->product_id,
                    'lot_no' => $order->mo_no,
                ])->id
                : null;

            $this->recordStockMovement->handle(
                reference: $order,
                teamId: $order->team_id,
                warehouseId: $order->warehouse_id,
                product: $order->product,
                type: StockMovementType::ProductionOutput,
                quantity: $quantity,
                user: $user,
                unitCostInBaseCurrency: $totalCost / $quantity,
                incomingLayers: $this->splitCostLayers->handle($totalCost, $quantity),
                stockLotId: $stockLotId,
            );

            $order->update(['status' => ManufacturingOrderStatus::Done, 'completed_at' => now()]);

            return $order->fresh('items');
        });
    }
}
