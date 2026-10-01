<?php

namespace App\Actions\Purchasing;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Facades\DB;

class ConfirmPurchaseOrder
{
    public function __construct(protected DocumentRuleEvaluator $documentRuleEvaluator) {}

    /**
     * Confirm a draft purchase order after checking the confirmation rules.
     *
     * @return DocumentResult<PurchaseOrder>
     */
    public function handle(PurchaseOrder $order): DocumentResult
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== PurchaseOrderStatus::Draft, 409, __('Only draft purchase orders can be confirmed.'));

            $warnings = $this->documentRuleEvaluator->ensurePasses($order->team()->firstOrFail(), DocumentType::PurchaseOrder, DocumentRuleEvent::Confirm, $order);

            $order->update(['status' => PurchaseOrderStatus::Confirmed]);

            return new DocumentResult($order->fresh('items'), $warnings);
        });
    }
}
