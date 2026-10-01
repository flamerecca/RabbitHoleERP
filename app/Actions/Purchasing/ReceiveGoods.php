<?php

namespace App\Actions\Purchasing;

use App\Actions\Inventory\RecordStockMovement;
use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\StockLot;
use App\Models\User;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveGoods
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Confirm a draft goods receipt: add the received quantities to stock in the stock unit, and to the lot of
     * each line for products tracked by lot, update the moving average cost, write back the received quantities
     * and the purchase order status.
     *
     * Journal entry posting is deferred until the accounting module exists, see docs/architecture.md section 5.
     *
     * @return DocumentResult<GoodsReceipt>
     *
     * @throws ValidationException
     */
    public function handle(GoodsReceipt $goodsReceipt, User $user): DocumentResult
    {
        return DB::transaction(function () use ($goodsReceipt, $user) {
            $goodsReceipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($goodsReceipt->id);

            abort_if($goodsReceipt->status !== DocumentStatus::Draft, 409, __('Only draft goods receipts can be confirmed.'));

            $purchaseOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($goodsReceipt->purchase_order_id);
            abort_unless(
                in_array($purchaseOrder->status, [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived], true),
                409,
                __('The purchase order does not allow receiving goods.'),
            );

            $warnings = $this->documentRuleEvaluator->ensurePasses($goodsReceipt->team()->firstOrFail(), DocumentType::GoodsReceipt, DocumentRuleEvent::Confirm, $goodsReceipt);

            if ($goodsReceipt->received_date->isAfter(today())) {
                throw ValidationException::withMessages(['received_date' => __('The received date cannot be later than today.')]);
            }

            $goodsReceipt->load(['purchaseOrder', 'items.product.unit', 'items.purchaseOrderItem.unit']);
            $lines = $goodsReceipt->items->filter(fn ($item) => (float) $item->quantity > 0);

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('The goods receipt must have at least one line with a quantity greater than zero.')]);
            }

            $receivedByOrderItem = [];
            foreach ($lines as $line) {
                $orderItem = $line->purchaseOrderItem;
                $receivedByOrderItem[$orderItem->id] = ($receivedByOrderItem[$orderItem->id] ?? (float) $orderItem->received_quantity) + (float) $line->quantity;

                abort_if(
                    round($receivedByOrderItem[$orderItem->id], 4) > (float) $orderItem->quantity,
                    409,
                    __('The received quantity exceeds the ordered quantity.'),
                );
            }

            foreach ($lines as $line) {
                $orderItem = $line->purchaseOrderItem;
                $stockUnit = $line->product->unit;
                $unitCostInBaseCurrency = round(
                    $orderItem->unit->convertPrice($line->unit_cost, $stockUnit) * (float) $goodsReceipt->purchaseOrder->exchange_rate,
                    4,
                );

                $stockLotId = $line->lot_no === null ? null : StockLot::query()->firstOrCreate([
                    'team_id' => $goodsReceipt->team_id,
                    'product_id' => $line->product_id,
                    'lot_no' => $line->lot_no,
                ])->id;

                $this->recordStockMovement->handle(
                    reference: $line,
                    teamId: $goodsReceipt->team_id,
                    warehouseId: $goodsReceipt->warehouse_id,
                    product: $line->product,
                    type: StockMovementType::PurchaseIn,
                    quantity: $orderItem->unit->convertQuantity($line->quantity, $stockUnit),
                    user: $user,
                    unitCostInBaseCurrency: $unitCostInBaseCurrency,
                    stockLotId: $stockLotId,
                );

                $orderItem->update(['received_quantity' => round((float) $orderItem->received_quantity + (float) $line->quantity, 4)]);
            }

            $goodsReceipt->update(['status' => DocumentStatus::Confirmed]);

            $purchaseOrder = $goodsReceipt->purchaseOrder->load('items');
            $isFullyReceived = $purchaseOrder->items->every(fn ($item) => (float) $item->received_quantity >= (float) $item->quantity);
            $purchaseOrder->update(['status' => $isFullyReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived]);

            return new DocumentResult($goodsReceipt->fresh(['items', 'purchaseOrder']), $warnings);
        });
    }
}
