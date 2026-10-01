<?php

namespace App\Enums;

/**
 * 庫存異動類型。
 */
enum StockMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case SalesOut = 'sales_out';
    case PurchaseReturnOut = 'purchase_return_out';
    case SalesReturnIn = 'sales_return_in';
    case ScrapOut = 'scrap_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case RequisitionOut = 'requisition_out';

    /**
     * Get the virtual location on the other side of the movement, or null for warehouse transfers.
     */
    public function virtualLocation(): ?VirtualLocation
    {
        return match ($this) {
            self::PurchaseIn, self::PurchaseReturnOut => VirtualLocation::Suppliers,
            self::SalesOut, self::SalesReturnIn => VirtualLocation::Customers,
            self::ScrapOut => VirtualLocation::Scrap,
            self::AdjustmentIn, self::AdjustmentOut => VirtualLocation::Inventory,
            self::RequisitionOut => VirtualLocation::Production,
            self::TransferIn, self::TransferOut => null,
        };
    }
}
