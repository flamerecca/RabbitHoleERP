<?php

namespace App\Actions\Purchasing;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Facades\DB;

class AddPurchaseOrderItem
{
    public function __construct(protected DocumentRuleEvaluator $documentRuleEvaluator) {}

    /**
     * Add a line to a draft, confirmed or partially received purchase order and check the update rules.
     *
     * @param  array<string, mixed>  $line
     * @return DocumentResult<PurchaseOrder>
     */
    public function handle(PurchaseOrder $order, array $line): DocumentResult
    {
        return DB::transaction(function () use ($order, $line) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_unless(
                in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived], true),
                409,
                __('Lines can only be added to draft, confirmed or partially received purchase orders.'),
            );

            $this->createLine($order, $line);
            $order->recalculateTotals();

            $warnings = $this->documentRuleEvaluator->ensurePasses($order->team()->firstOrFail(), DocumentType::PurchaseOrder, DocumentRuleEvent::Update, $order);

            return new DocumentResult($order->fresh('items'), $warnings);
        });
    }

    /**
     * Create a purchase order line, defaulting the unit to the product purchase unit.
     *
     * @param  array<string, mixed>  $line
     */
    public function createLine(PurchaseOrder $order, array $line): PurchaseOrderItem
    {
        $product = Product::query()->whereKey($line['product_id'])->firstOrFail();

        return $order->items()->create([
            'product_id' => $product->id,
            'unit_id' => $line['unit_id'] ?? $product->purchase_unit_id,
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'tax_rate_id' => $line['tax_rate_id'] ?? null,
            'received_quantity' => 0,
        ]);
    }
}
