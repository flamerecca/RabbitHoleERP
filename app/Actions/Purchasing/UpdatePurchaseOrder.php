<?php

namespace App\Actions\Purchasing;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdatePurchaseOrder
{
    public function __construct(
        protected AddPurchaseOrderItem $addPurchaseOrderItem,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Update the header of a draft purchase order and replace its lines when given, then check the update rules.
     *
     * @param  array<string, mixed>  $attributes
     * @return DocumentResult<PurchaseOrder>
     */
    public function handle(PurchaseOrder $order, array $attributes): DocumentResult
    {
        return DB::transaction(function () use ($order, $attributes) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== PurchaseOrderStatus::Draft, 409, __('Only draft purchase orders can be updated.'));

            $order->update(Arr::only($attributes, ['supplier_id', 'warehouse_id', 'currency_id', 'exchange_rate', 'order_date']));

            if (array_key_exists('items', $attributes)) {
                $order->items()->get()->each->delete();

                foreach ($attributes['items'] as $line) {
                    $this->addPurchaseOrderItem->createLine($order, $line);
                }
            }

            $order->recalculateTotals();

            $warnings = $this->documentRuleEvaluator->ensurePasses($order->team()->firstOrFail(), DocumentType::PurchaseOrder, DocumentRuleEvent::Update, $order);

            return new DocumentResult($order->fresh('items'), $warnings);
        });
    }
}
