<?php

namespace App\Actions\Inventory;

use App\Enums\CostMethod;
use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\StockCostLayerConsumption;
use App\Models\User;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Support\Facades\DB;

class TransferStock
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected EnsureStockAvailable $ensureStockAvailable,
        protected AllocateStockLots $allocateStockLots,
    ) {}

    /**
     * Confirm a draft transfer: move unreserved stock from the source to the destination warehouse at unchanged cost.
     *
     * FIFO products recreate every consumed source layer in the destination warehouse with the same unit cost,
     * and products tracked by lot keep their lots, taken from the oldest lots first.
     */
    public function handle(WarehouseTransfer $document, User $user): WarehouseTransfer
    {
        return DB::transaction(function () use ($document, $user) {
            $document = WarehouseTransfer::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft warehouse transfers can be confirmed.'));

            $document->load('items.product.category');
            $requiredByProduct = $document->items->groupBy('product_id')->map(fn ($lines) => (float) $lines->sum('quantity'))->all();
            $this->ensureStockAvailable->handle($document->from_warehouse_id, $requiredByProduct, $document->items->pluck('product', 'product_id'));

            foreach ($document->items as $line) {
                foreach ($this->allocateStockLots->handle($document->from_warehouse_id, $line->product, (float) $line->quantity) as $allocation) {
                    $this->transferAllocation($document, $line, $allocation['quantity'], $allocation['stock_lot_id'], $user);
                }
            }

            $document->update(['status' => DocumentStatus::Confirmed]);

            return $document->fresh('items');
        });
    }

    /**
     * Move one lot allocation of a line out of the source and into the destination warehouse.
     */
    protected function transferAllocation(WarehouseTransfer $document, WarehouseTransferItem $line, float $quantity, ?int $stockLotId, User $user): void
    {
        $out = $this->recordStockMovement->handle(
            reference: $line,
            teamId: $document->team_id,
            warehouseId: $document->from_warehouse_id,
            product: $line->product,
            type: StockMovementType::TransferOut,
            quantity: -$quantity,
            user: $user,
            stockLotId: $stockLotId,
        );

        $incomingLayers = $line->product->costMethod() === CostMethod::Fifo
            ? array_values(StockCostLayerConsumption::query()
                ->where('stock_movement_id', $out->id)
                ->orderBy('id')
                ->get()
                ->map(fn (StockCostLayerConsumption $consumption) => ['quantity' => (float) $consumption->quantity, 'unit_cost' => (float) $consumption->unit_cost])
                ->all()) ?: null
            : null;

        $this->recordStockMovement->handle(
            reference: $line,
            teamId: $document->team_id,
            warehouseId: $document->to_warehouse_id,
            product: $line->product,
            type: StockMovementType::TransferIn,
            quantity: $quantity,
            user: $user,
            unitCostInBaseCurrency: round((float) $out->total_cost / $quantity, 4),
            incomingLayers: $incomingLayers,
            stockLotId: $stockLotId,
        );
    }
}
