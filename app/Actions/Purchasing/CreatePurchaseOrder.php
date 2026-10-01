<?php

namespace App\Actions\Purchasing;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentRuleEvaluator;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrder
{
    public function __construct(
        protected DocumentSequence $documentSequence,
        protected AddPurchaseOrderItem $addPurchaseOrderItem,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Create a draft purchase order with its lines and check the creation rules.
     *
     * @param  array<string, mixed>  $attributes  The validated purchase order with its `items` lines.
     * @return DocumentResult<PurchaseOrder>
     */
    public function handle(Team $team, User $user, array $attributes): DocumentResult
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $order = PurchaseOrder::create([
                'team_id' => $team->id,
                'supplier_id' => $attributes['supplier_id'],
                'warehouse_id' => $attributes['warehouse_id'],
                'currency_id' => $attributes['currency_id'],
                'exchange_rate' => $attributes['exchange_rate'],
                'order_no' => $this->documentSequence->next($team, DocumentType::PurchaseOrder, CarbonImmutable::parse((string) $attributes['order_date'])),
                'status' => PurchaseOrderStatus::Draft,
                'order_date' => $attributes['order_date'],
                'created_by' => $user->id,
            ]);

            foreach ((array) $attributes['items'] as $line) {
                $this->addPurchaseOrderItem->createLine($order, $line);
            }

            $order->recalculateTotals();

            $warnings = $this->documentRuleEvaluator->ensurePasses($team, DocumentType::PurchaseOrder, DocumentRuleEvent::Create, $order);

            return new DocumentResult($order->fresh('items'), $warnings);
        });
    }
}
