<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\SalesReturnItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\StockLotBalance;
use App\Models\StockMovement;

/**
 * Quantities of a lot in the stock unit, used to validate lot returns and to size whole-lot returns.
 */
class LotQuantities
{
    /**
     * Get the stock of the lot in the warehouse.
     */
    public static function onHand(int $warehouseId, int $stockLotId): float
    {
        return (float) StockLotBalance::query()->where('warehouse_id', $warehouseId)->where('stock_lot_id', $stockLotId)->value('quantity_on_hand');
    }

    /**
     * Determine if the goods receipt put the lot into stock.
     */
    public static function wasReceivedBy(GoodsReceipt $goodsReceipt, int $stockLotId): bool
    {
        return StockMovement::query()
            ->where('type', StockMovementType::PurchaseIn)
            ->where('reference_type', (new GoodsReceiptItem)->getMorphClass())
            ->whereIn('reference_id', $goodsReceipt->items()->select('id'))
            ->where('stock_lot_id', $stockLotId)
            ->exists();
    }

    /**
     * Get how much of the lot the shipment sent to the customer.
     */
    public static function shippedBy(Shipment $shipment, int $stockLotId): float
    {
        return abs((float) StockMovement::query()
            ->where('type', StockMovementType::SalesOut)
            ->where('reference_type', (new ShipmentItem)->getMorphClass())
            ->whereIn('reference_id', $shipment->items()->select('id'))
            ->where('stock_lot_id', $stockLotId)
            ->sum('quantity'));
    }

    /**
     * Get how much of the lot confirmed sales returns already took back from the shipment.
     */
    public static function returnedFrom(Shipment $shipment, int $stockLotId): float
    {
        return (float) SalesReturnItem::query()
            ->whereHas('salesReturn', fn ($query) => $query->where('shipment_id', $shipment->id)->where('status', DocumentStatus::Confirmed))
            ->where('stock_lot_id', $stockLotId)
            ->sum('quantity');
    }
}
