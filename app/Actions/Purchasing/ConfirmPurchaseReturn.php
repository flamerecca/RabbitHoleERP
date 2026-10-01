<?php

namespace App\Actions\Purchasing;

use App\Actions\Inventory\EnsureStockAvailable;
use App\Actions\Inventory\RecordStockMovement;
use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmPurchaseReturn
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected EnsureStockAvailable $ensureStockAvailable,
    ) {}

    /**
     * Confirm a draft purchase return: check the returned quantities against the goods receipt and the
     * unreserved stock, then send the goods back to the supplier from their lots, consuming the receipt's FIFO
     * layers first. Whole-lot lines take the lot's stock at confirmation time.
     *
     * Journal entry posting is deferred until the accounting module exists, see docs/architecture.md section 5.
     */
    public function handle(PurchaseReturn $document, User $user): PurchaseReturn
    {
        return DB::transaction(function () use ($document, $user) {
            $document = PurchaseReturn::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft purchase returns can be confirmed.'));

            CreatePurchaseReturn::refreshWholeLots($document);

            $document->load(['items.product.unit', 'goodsReceipt.items.purchaseOrderItem.unit', 'goodsReceipt.items.product.unit']);
            $requiredByProduct = $document->items->groupBy('product_id')->map(fn ($lines) => (float) $lines->sum('quantity'))->all();

            $receivedByProduct = [];
            foreach ($document->goodsReceipt->items as $receiptItem) {
                $receivedByProduct[$receiptItem->product_id] = ($receivedByProduct[$receiptItem->product_id] ?? 0.0)
                    + $receiptItem->purchaseOrderItem->unit->convertQuantity($receiptItem->quantity, $receiptItem->product->unit);
            }

            foreach ($requiredByProduct as $productId => $required) {
                $alreadyReturned = (float) PurchaseReturnItem::query()
                    ->whereHas('purchaseReturn', fn ($query) => $query->where('goods_receipt_id', $document->goods_receipt_id)->where('status', DocumentStatus::Confirmed))
                    ->where('product_id', $productId)
                    ->sum('quantity');

                abort_if(
                    round($alreadyReturned + $required, 4) > round($receivedByProduct[$productId] ?? 0.0, 4),
                    409,
                    __('The returned quantity exceeds the received quantity.'),
                );
            }

            $this->ensureStockAvailable->handle($document->warehouse_id, $requiredByProduct, $document->items->pluck('product', 'product_id'));

            $receiptMovementIds = array_values(StockMovement::query()
                ->where('reference_type', (new GoodsReceiptItem)->getMorphClass())
                ->whereIn('reference_id', $document->goodsReceipt->items->modelKeys())
                ->get(['id'])
                ->map(fn (StockMovement $movement): int => $movement->id)
                ->all());

            foreach ($document->items as $line) {
                $this->recordStockMovement->handle(
                    reference: $line,
                    teamId: $document->team_id,
                    warehouseId: $document->warehouse_id,
                    product: $line->product,
                    type: StockMovementType::PurchaseReturnOut,
                    quantity: -(float) $line->quantity,
                    user: $user,
                    preferredLayerMovementIds: $receiptMovementIds,
                    stockLotId: $line->stock_lot_id,
                );
            }

            $document->update(['status' => DocumentStatus::Confirmed]);

            return $document->fresh('items');
        });
    }
}
