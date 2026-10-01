<?php

namespace App\Enums;

/**
 * 會產生單號的單據類型。
 */
enum DocumentType: string
{
    case PurchaseOrder = 'purchase_order';
    case GoodsReceipt = 'goods_receipt';
    case PurchaseReturn = 'purchase_return';
    case SalesOrder = 'sales_order';
    case Shipment = 'shipment';
    case SalesReturn = 'sales_return';
    case ConsignmentHold = 'consignment_hold';
    case WarehouseTransfer = 'warehouse_transfer';
    case StockTake = 'stock_take';
    case MaterialRequisition = 'material_requisition';
    case JournalEntry = 'journal_entry';
    case PaymentReceipt = 'payment_receipt';
    case PaymentPayment = 'payment_payment';

    /**
     * Get the default document number prefix.
     */
    public function defaultPrefix(): string
    {
        return match ($this) {
            self::PurchaseOrder => 'PO',
            self::GoodsReceipt => 'GR',
            self::PurchaseReturn => 'PR',
            self::SalesOrder => 'SO',
            self::Shipment => 'SH',
            self::SalesReturn => 'SR',
            self::ConsignmentHold => 'CH',
            self::WarehouseTransfer => 'WT',
            self::StockTake => 'ST',
            self::MaterialRequisition => 'MR',
            self::JournalEntry => 'JE',
            self::PaymentReceipt => 'RC',
            self::PaymentPayment => 'PY',
        };
    }

    /**
     * Get the table and column holding the document number, or null when the document is not implemented yet.
     *
     * @return array{0: string, 1: string}|null
     */
    public function numberColumn(): ?array
    {
        return match ($this) {
            self::PurchaseOrder => ['purchase_orders', 'order_no'],
            self::GoodsReceipt => ['goods_receipts', 'receipt_no'],
            self::SalesOrder => ['sales_orders', 'order_no'],
            self::Shipment => ['shipments', 'shipment_no'],
            self::PurchaseReturn => ['purchase_returns', 'return_no'],
            self::SalesReturn => ['sales_returns', 'return_no'],
            self::ConsignmentHold => ['consignment_holds', 'hold_no'],
            self::WarehouseTransfer => ['warehouse_transfers', 'transfer_no'],
            self::StockTake => ['stock_takes', 'take_no'],
            self::MaterialRequisition => ['material_requisitions', 'requisition_no'],
            default => null,
        };
    }

    /**
     * Determine if document rules can be configured for the document type.
     */
    public function supportsRules(): bool
    {
        return in_array($this, [self::PurchaseOrder, self::GoodsReceipt, self::SalesOrder, self::Shipment], true);
    }
}
