<?php

namespace App\Actions\Accounting;

use App\Enums\JournalEntryStatus;
use App\Enums\PartnerType;
use App\Enums\SalesReturnDisposition;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\MaterialRequisition;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The automatic posting rules of the confirmed business documents, see docs/architecture/accounting-schema.md section 2.
 *
 * Each method is called by the confirm action of its document inside the same transaction, after the stock movements
 * are recorded, so the cost of goods comes from `stock_movements.total_cost`.
 */
class PostDocumentJournalEntries
{
    public function __construct(protected PostJournalEntry $postJournalEntry)
    {
    }

    /**
     * Debit inventory and credit the supplier's payable with the received cost.
     */
    public function goodsReceipt(GoodsReceipt $goodsReceipt, User $user): void
    {
        $goodsReceipt->loadMissing(['team', 'purchaseOrder', 'items']);
        $settings = PostJournalEntry::settings($goodsReceipt->team);
        $order = $goodsReceipt->purchaseOrder;

        $amountCurrency = (float) $goodsReceipt->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_cost);
        $amount = round($amountCurrency * (float) $order->exchange_rate, 4);

        $this->postJournalEntry->handle($goodsReceipt, $goodsReceipt->team, $settings->purchase_journal_id, $goodsReceipt->received_date, $user, [
            ['account_id' => $settings->inventory_account_id, 'debit' => $amount],
            [
                'account_id' => $settings->payable_account_id,
                'credit' => $amount,
                'partner_type' => PartnerType::Supplier,
                'partner_id' => $order->supplier_id,
                'currency_id' => $order->currency_id,
                'exchange_rate' => (float) $order->exchange_rate,
                'amount_currency' => $amountCurrency,
            ],
        ], $goodsReceipt->receipt_no);
    }

    /**
     * Debit the customer's receivable and credit sales revenue with the sales price, then move the cost of the
     * shipped goods from inventory to cost of goods sold.
     */
    public function shipment(Shipment $shipment, User $user): void
    {
        $shipment->loadMissing(['team', 'salesOrder', 'items.salesOrderItem']);
        $settings = PostJournalEntry::settings($shipment->team);
        $order = $shipment->salesOrder;

        $amountCurrency = (float) $shipment->items->sum(fn ($item) => (float) $item->quantity * (float) $item->salesOrderItem->unit_price);
        $amount = round($amountCurrency * (float) $order->exchange_rate, 4);

        $this->postJournalEntry->handle($shipment, $shipment->team, $settings->sales_journal_id, $shipment->shipped_date, $user, [
            [
                'account_id' => $settings->receivable_account_id,
                'debit' => $amount,
                'partner_type' => PartnerType::Customer,
                'partner_id' => $order->customer_id,
                'currency_id' => $order->currency_id,
                'exchange_rate' => (float) $order->exchange_rate,
                'amount_currency' => $amountCurrency,
            ],
            ['account_id' => $settings->sales_revenue_account_id, 'credit' => $amount],
        ], $shipment->shipment_no);

        $cost = $this->movementCost($shipment->items, StockMovementType::SalesOut);

        $this->postJournalEntry->handle($shipment, $shipment->team, $settings->general_journal_id, $shipment->shipped_date, $user, [
            ['account_id' => $settings->cost_of_goods_sold_account_id, 'debit' => $cost],
            ['account_id' => $settings->inventory_account_id, 'credit' => $cost],
        ], $shipment->shipment_no);
    }

    /**
     * Debit the supplier's payable and credit inventory with the returned cost, settling the payable of the goods receipt.
     */
    public function purchaseReturn(PurchaseReturn $purchaseReturn, User $user): void
    {
        $purchaseReturn->loadMissing(['team', 'items', 'goodsReceipt.purchaseOrder']);
        $settings = PostJournalEntry::settings($purchaseReturn->team);
        $order = $purchaseReturn->goodsReceipt->purchaseOrder;

        $amountCurrency = (float) $purchaseReturn->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_cost);
        $amount = round($amountCurrency * (float) $order->exchange_rate, 4);

        $entry = $this->postJournalEntry->handle($purchaseReturn, $purchaseReturn->team, $settings->purchase_journal_id, $purchaseReturn->return_date, $user, [
            [
                'account_id' => $settings->payable_account_id,
                'debit' => $amount,
                'partner_type' => PartnerType::Supplier,
                'partner_id' => $purchaseReturn->supplier_id,
                'currency_id' => $order->currency_id,
                'exchange_rate' => (float) $order->exchange_rate,
                'amount_currency' => $amountCurrency,
            ],
            ['account_id' => $settings->inventory_account_id, 'credit' => $amount],
        ], $purchaseReturn->return_no);

        $this->settleSourceDocument($entry, $settings->payable_account_id, $purchaseReturn->goodsReceipt);
    }

    /**
     * Debit sales revenue and credit the customer's receivable with the returned sales price, settling the receivable
     * of the shipment, then move the cost of restocked goods back from cost of goods sold to inventory.
     */
    public function salesReturn(SalesReturn $salesReturn, User $user): void
    {
        $salesReturn->loadMissing(['team', 'items.product.unit', 'shipment.salesOrder', 'shipment.items.salesOrderItem.unit', 'shipment.items.product.unit']);
        $settings = PostJournalEntry::settings($salesReturn->team);
        $order = $salesReturn->shipment->salesOrder;

        $amountCurrency = (float) $salesReturn->items->sum(
            fn ($item) => (float) $item->quantity * $this->shippedUnitPrice($salesReturn->shipment, $item->product_id),
        );
        $amount = round($amountCurrency * (float) $order->exchange_rate, 4);

        $entry = $this->postJournalEntry->handle($salesReturn, $salesReturn->team, $settings->sales_journal_id, $salesReturn->return_date, $user, [
            ['account_id' => $settings->sales_revenue_account_id, 'debit' => $amount],
            [
                'account_id' => $settings->receivable_account_id,
                'credit' => $amount,
                'partner_type' => PartnerType::Customer,
                'partner_id' => $salesReturn->customer_id,
                'currency_id' => $order->currency_id,
                'exchange_rate' => (float) $order->exchange_rate,
                'amount_currency' => $amountCurrency,
            ],
        ], $salesReturn->return_no);

        $this->settleSourceDocument($entry, $settings->receivable_account_id, $salesReturn->shipment);

        $cost = $this->movementCost(
            $salesReturn->items->whereIn('disposition', [SalesReturnDisposition::Restock, SalesReturnDisposition::Refurbish]),
            StockMovementType::SalesReturnIn,
        );

        $this->postJournalEntry->handle($salesReturn, $salesReturn->team, $settings->general_journal_id, $salesReturn->return_date, $user, [
            ['account_id' => $settings->inventory_account_id, 'debit' => $cost],
            ['account_id' => $settings->cost_of_goods_sold_account_id, 'credit' => $cost],
        ], $salesReturn->return_no);
    }

    /**
     * Debit research expense and credit inventory with the cost of the requisitioned goods.
     */
    public function materialRequisition(MaterialRequisition $materialRequisition, User $user): void
    {
        $materialRequisition->loadMissing(['team', 'items']);
        $settings = PostJournalEntry::settings($materialRequisition->team);
        $cost = $this->movementCost($materialRequisition->items, StockMovementType::RequisitionOut);

        $this->postJournalEntry->handle($materialRequisition, $materialRequisition->team, $settings->general_journal_id, $materialRequisition->requisition_date, $user, [
            ['account_id' => $settings->research_expense_account_id, 'debit' => $cost],
            ['account_id' => $settings->inventory_account_id, 'credit' => $cost],
        ], $materialRequisition->requisition_no);
    }

    /**
     * Post the stock take surplus against the inventory adjustment account, then the shortage.
     */
    public function stockTake(StockTake $stockTake, User $user): void
    {
        $stockTake->loadMissing(['team', 'items']);
        $settings = PostJournalEntry::settings($stockTake->team);
        $surplus = $this->movementCost($stockTake->items, StockMovementType::AdjustmentIn);
        $shortage = $this->movementCost($stockTake->items, StockMovementType::AdjustmentOut);

        $this->postJournalEntry->handle($stockTake, $stockTake->team, $settings->general_journal_id, $stockTake->taken_date, $user, [
            ['account_id' => $settings->inventory_account_id, 'debit' => $surplus],
            ['account_id' => $settings->inventory_adjustment_account_id, 'credit' => $surplus],
        ], $stockTake->take_no);

        $this->postJournalEntry->handle($stockTake, $stockTake->team, $settings->general_journal_id, $stockTake->taken_date, $user, [
            ['account_id' => $settings->inventory_adjustment_account_id, 'debit' => $shortage],
            ['account_id' => $settings->inventory_account_id, 'credit' => $shortage],
        ], $stockTake->take_no);
    }

    /**
     * Get the total cost of the stock movements of the given type recorded for the document lines.
     *
     * @param  Collection<int, covariant Model>  $lines
     */
    protected function movementCost(Collection $lines, StockMovementType $type): float
    {
        if ($lines->isEmpty()) {
            return 0.0;
        }

        return round((float) StockMovement::query()
            ->where('reference_type', $lines->first()->getMorphClass())
            ->whereIn('reference_id', $lines->modelKeys())
            ->where('type', $type)
            ->get(['total_cost'])
            ->sum(fn (StockMovement $movement) => abs((float) $movement->total_cost)), 4);
    }

    /**
     * Get the average sales price per stock unit, in the order currency, at which the shipment sent the product out.
     */
    protected function shippedUnitPrice(Shipment $shipment, int $productId): float
    {
        $lines = $shipment->items->where('product_id', $productId);
        $quantity = (float) $lines->sum(fn ($item) => $item->salesOrderItem->unit->convertQuantity($item->quantity, $item->product->unit));
        $price = (float) $lines->sum(fn ($item) => (float) $item->quantity * (float) $item->salesOrderItem->unit_price);

        return $quantity > 0 ? $price / $quantity : 0.0;
    }

    /**
     * Settle the open receivable or payable of the source document with the residual of the return's line.
     */
    protected function settleSourceDocument(?JournalEntry $returnEntry, int $accountId, Model $sourceDocument): void
    {
        $returnLine = $returnEntry?->lines->firstWhere('account_id', $accountId);

        if ($returnLine === null) {
            return;
        }

        $sourceLines = JournalEntryLine::query()
            ->where('account_id', $accountId)
            ->where('partner_type', $returnLine->partner_type)
            ->where('partner_id', $returnLine->partner_id)
            ->where('amount_residual', '>', 0)
            ->whereHas('journalEntry', fn ($query) => $query
                ->where('reference_type', $sourceDocument->getMorphClass())
                ->where('reference_id', $sourceDocument->getKey())
                ->where('status', JournalEntryStatus::Posted))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($sourceLines as $sourceLine) {
            $settled = min((float) $sourceLine->amount_residual, (float) $returnLine->amount_residual);

            if ($settled <= 0) {
                return;
            }

            $sourceLine->update(['amount_residual' => round((float) $sourceLine->amount_residual - $settled, 4)]);
            $returnLine->update(['amount_residual' => round((float) $returnLine->amount_residual - $settled, 4)]);
        }
    }
}
