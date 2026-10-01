<?php

namespace App\Actions\Sales;

use App\Actions\Inventory\AllocateStockLots;
use App\Actions\Inventory\RecordStockMovement;
use App\Actions\Inventory\UnreserveSalesOrderStock;
use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipSalesOrder
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
        protected UnreserveSalesOrderStock $unreserveSalesOrderStock,
        protected AllocateStockLots $allocateStockLots,
    ) {}

    /**
     * Confirm a draft shipment: check available stock in the stock unit, deduct it, release the
     * reservation of the shipped order lines, write back the shipped quantities and the sales order status.
     *
     * The order may use the stock reserved for its own shipped lines, but not the stock reserved for other orders.
     * Lines of products tracked by lot ship from their chosen lot, or from the oldest lots first.
     *
     * Journal entry posting is deferred until the accounting module exists, see docs/architecture.md section 5.
     *
     * @return DocumentResult<Shipment>
     *
     * @throws ValidationException
     */
    public function handle(Shipment $shipment, User $user): DocumentResult
    {
        return DB::transaction(function () use ($shipment, $user) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            abort_if($shipment->status !== DocumentStatus::Draft, 409, __('Only draft shipments can be confirmed.'));

            $salesOrder = SalesOrder::query()->lockForUpdate()->findOrFail($shipment->sales_order_id);
            abort_unless(
                in_array($salesOrder->status, [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped], true),
                409,
                __('The sales order does not allow shipping.'),
            );

            $warnings = $this->documentRuleEvaluator->ensurePasses($shipment->team()->firstOrFail(), DocumentType::Shipment, DocumentRuleEvent::Confirm, $shipment);

            $shipment->load(['items.product.unit', 'items.salesOrderItem.unit']);
            $lines = $shipment->items->filter(fn ($item) => (float) $item->quantity > 0);

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('The shipment must have at least one line with a quantity greater than zero.')]);
            }

            $shippedByOrderItem = [];
            $requiredByProduct = [];
            $ownReservationByProduct = [];
            foreach ($lines as $line) {
                $orderItem = $line->salesOrderItem;
                $shippedByOrderItem[$orderItem->id] = ($shippedByOrderItem[$orderItem->id] ?? (float) $orderItem->shipped_quantity) + (float) $line->quantity;

                abort_if(
                    round($shippedByOrderItem[$orderItem->id], 4) > (float) $orderItem->quantity,
                    409,
                    __('The shipped quantity exceeds the ordered quantity.'),
                );

                $requiredByProduct[$line->product_id] = ($requiredByProduct[$line->product_id] ?? 0.0)
                    + $orderItem->unit->convertQuantity($line->quantity, $line->product->unit);
                $ownReservationByProduct[$line->product_id][$orderItem->id] = (float) $orderItem->reserved_quantity;
            }

            foreach ($requiredByProduct as $productId => $required) {
                $balance = StockBalance::query()
                    ->where('warehouse_id', $shipment->warehouse_id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
                $available = $balance === null
                    ? 0.0
                    : (float) $balance->quantity_on_hand - (float) $balance->quantity_reserved + array_sum($ownReservationByProduct[$productId]);

                abort_if(
                    round($required, 4) > round($available, 4),
                    409,
                    __('Insufficient stock for product [:sku].', ['sku' => $lines->firstWhere('product_id', $productId)?->product->sku]),
                );
            }

            foreach ($lines as $line) {
                $orderItem = $line->salesOrderItem;

                $shippedInStockUnit = $orderItem->unit->convertQuantity($line->quantity, $line->product->unit);

                foreach ($this->allocateStockLots->handle($shipment->warehouse_id, $line->product, $shippedInStockUnit, $line->stock_lot_id) as $allocation) {
                    $this->recordStockMovement->handle(
                        reference: $line,
                        teamId: $shipment->team_id,
                        warehouseId: $shipment->warehouse_id,
                        product: $line->product,
                        type: StockMovementType::SalesOut,
                        quantity: -$allocation['quantity'],
                        user: $user,
                        stockLotId: $allocation['stock_lot_id'],
                    );
                }

                $orderItem->update(['shipped_quantity' => round((float) $orderItem->shipped_quantity + (float) $line->quantity, 4)]);

                $isLineFullyShipped = (float) $orderItem->shipped_quantity >= (float) $orderItem->quantity;
                $this->unreserveSalesOrderStock->release(
                    $shipment->warehouse_id,
                    $orderItem,
                    $isLineFullyShipped ? (float) $orderItem->reserved_quantity : $shippedInStockUnit,
                );
            }

            $shipment->update(['status' => DocumentStatus::Confirmed]);

            $salesOrder->load('items');
            $isFullyShipped = $salesOrder->items->every(fn ($item) => (float) $item->shipped_quantity >= (float) $item->quantity);
            $salesOrder->update(['status' => $isFullyShipped ? SalesOrderStatus::Shipped : SalesOrderStatus::PartiallyShipped]);

            return new DocumentResult($shipment->fresh(['items']), $warnings);
        });
    }
}
