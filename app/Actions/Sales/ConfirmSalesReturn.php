<?php

namespace App\Actions\Sales;

use App\Actions\Inventory\LotQuantities;
use App\Actions\Inventory\RecordStockMovement;
use App\Enums\DocumentStatus;
use App\Enums\SalesReturnDisposition;
use App\Enums\StockMovementType;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\ShipmentItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmSalesReturn
{
    public function __construct(protected RecordStockMovement $recordStockMovement) {}

    /**
     * Confirm a draft sales return: check the returned quantities against the shipment, take the goods back
     * at their original shipping cost into their lots and move scrapped goods straight out of the sellable stock.
     * Whole-lot lines take what is left of the lot at confirmation time.
     *
     * Journal entry posting is deferred until the accounting module exists, see docs/architecture.md section 5.
     */
    public function handle(SalesReturn $document, User $user): SalesReturn
    {
        return DB::transaction(function () use ($document, $user) {
            $document = SalesReturn::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft sales returns can be confirmed.'));

            CreateSalesReturn::refreshWholeLots($document);

            $document->load(['items.product.unit', 'shipment.items.salesOrderItem.unit', 'shipment.items.product.unit']);

            $shippedByProduct = [];
            foreach ($document->shipment->items as $shipmentItem) {
                $shippedByProduct[$shipmentItem->product_id] = ($shippedByProduct[$shipmentItem->product_id] ?? 0.0)
                    + $shipmentItem->salesOrderItem->unit->convertQuantity($shipmentItem->quantity, $shipmentItem->product->unit);
            }

            foreach ($document->items->groupBy('product_id') as $productId => $lines) {
                $alreadyReturned = (float) SalesReturnItem::query()
                    ->whereHas('salesReturn', fn ($query) => $query->where('shipment_id', $document->shipment_id)->where('status', DocumentStatus::Confirmed))
                    ->where('product_id', $productId)
                    ->sum('quantity');

                abort_if(
                    round($alreadyReturned + (float) $lines->sum('quantity'), 4) > round($shippedByProduct[$productId] ?? 0.0, 4),
                    409,
                    __('The returned quantity exceeds the shipped quantity.'),
                );
            }

            foreach ($document->items->whereNotNull('stock_lot_id')->groupBy('stock_lot_id') as $stockLotId => $lines) {
                abort_if(
                    round(LotQuantities::returnedFrom($document->shipment, (int) $stockLotId) + (float) $lines->sum('quantity'), 4) > round(LotQuantities::shippedBy($document->shipment, (int) $stockLotId), 4),
                    409,
                    __('The returned quantity exceeds the shipped quantity of the lot.'),
                );
            }

            foreach ($document->items as $line) {
                $returned = $this->recordStockMovement->handle(
                    reference: $line,
                    teamId: $document->team_id,
                    warehouseId: $document->warehouse_id,
                    product: $line->product,
                    type: StockMovementType::SalesReturnIn,
                    quantity: (float) $line->quantity,
                    user: $user,
                    unitCostInBaseCurrency: $this->shippedUnitCost($document, $line->product_id),
                    stockLotId: $line->stock_lot_id,
                );

                if ($line->disposition === SalesReturnDisposition::Scrap) {
                    $this->recordStockMovement->handle(
                        reference: $line,
                        teamId: $document->team_id,
                        warehouseId: $document->warehouse_id,
                        product: $line->product,
                        type: StockMovementType::ScrapOut,
                        quantity: -(float) $line->quantity,
                        user: $user,
                        preferredLayerMovementIds: [$returned->id],
                        stockLotId: $line->stock_lot_id,
                    );
                }
            }

            $document->update(['status' => DocumentStatus::Confirmed]);

            return $document->fresh('items');
        });
    }

    /**
     * Get the average base currency cost per stock unit at which the shipment sent the product out.
     */
    protected function shippedUnitCost(SalesReturn $document, int $productId): float
    {
        $movements = StockMovement::query()
            ->where('reference_type', (new ShipmentItem)->getMorphClass())
            ->whereIn('reference_id', $document->shipment->items->where('product_id', $productId)->modelKeys())
            ->get(['quantity', 'total_cost']);
        $quantity = abs((float) $movements->sum('quantity'));

        return $quantity > 0 ? round((float) $movements->sum('total_cost') / $quantity, 4) : 0.0;
    }
}
