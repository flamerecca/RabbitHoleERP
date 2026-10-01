<?php

namespace App\Services;

use App\Enums\DocumentRuleAction;
use App\Enums\DocumentRuleCondition;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\DocumentRule;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DocumentRuleEvaluator
{
    /**
     * Evaluate the active rules of the team for the document and event.
     *
     * @return array{blocked: list<string>, warnings: list<string>}
     */
    public function evaluate(Team $team, DocumentType $documentType, DocumentRuleEvent $event, Model $document): array
    {
        $rules = DocumentRule::query()
            ->where('team_id', $team->id)
            ->where('document_type', $documentType)
            ->where('event', $event)
            ->where('is_active', true)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $result = ['blocked' => [], 'warnings' => []];

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $document)) {
                continue;
            }

            if ($rule->action === DocumentRuleAction::Block) {
                $result['blocked'][] = $rule->message;
            } else {
                $result['warnings'][] = $rule->message;
            }
        }

        return $result;
    }

    /**
     * Evaluate the rules, failing validation on the `rules` key when a blocking rule matches.
     *
     * @return list<string>
     *
     * @throws ValidationException
     */
    public function ensurePasses(Team $team, DocumentType $documentType, DocumentRuleEvent $event, Model $document): array
    {
        $result = $this->evaluate($team, $documentType, $event, $document);

        if ($result['blocked'] !== []) {
            throw ValidationException::withMessages(['rules' => $result['blocked']]);
        }

        return $result['warnings'];
    }

    /**
     * Determine if the rule condition holds for the document.
     */
    protected function matches(DocumentRule $rule, Model $document): bool
    {
        return match ($rule->condition_type) {
            DocumentRuleCondition::TotalAmountAbove => $this->totalInBaseCurrency($document) > (float) $rule->threshold,
            DocumentRuleCondition::LineQuantityAbove => $this->largestLineQuantityInStockUnit($document) > (float) $rule->threshold,
            DocumentRuleCondition::PartnerInList => in_array($this->partnerId($document), $rule->partner_ids ?? [], true),
            DocumentRuleCondition::CreditLimitExceeded => $this->exceedsCreditLimit($document),
        };
    }

    /**
     * Get the order total converted into the base currency.
     */
    protected function totalInBaseCurrency(Model $document): float
    {
        if (! $document instanceof PurchaseOrder && ! $document instanceof SalesOrder) {
            throw new InvalidArgumentException('Only orders have a total amount.');
        }

        return (float) $document->total_amount * (float) $document->exchange_rate;
    }

    /**
     * Get the largest line quantity of the document converted into the product stock unit.
     */
    protected function largestLineQuantityInStockUnit(Model $document): float
    {
        $quantities = match (true) {
            $document instanceof PurchaseOrder, $document instanceof SalesOrder => $document->items()->with(['unit', 'product.unit'])->get()
                ->map(fn ($item) => $item->unit->convertQuantity($item->quantity, $item->product->unit)),
            $document instanceof GoodsReceipt => $document->items()->with(['purchaseOrderItem.unit', 'product.unit'])->get()
                ->map(fn ($item) => $item->purchaseOrderItem->unit->convertQuantity($item->quantity, $item->product->unit)),
            $document instanceof Shipment => $document->items()->with(['salesOrderItem.unit', 'product.unit'])->get()
                ->map(fn ($item) => $item->salesOrderItem->unit->convertQuantity($item->quantity, $item->product->unit)),
            default => throw new InvalidArgumentException('Unsupported document for document rules.'),
        };

        return (float) ($quantities->max() ?? 0);
    }

    /**
     * Get the supplier or customer id of the document.
     */
    protected function partnerId(Model $document): int
    {
        return match (true) {
            $document instanceof PurchaseOrder => $document->supplier_id,
            $document instanceof SalesOrder => $document->customer_id,
            $document instanceof GoodsReceipt => $document->purchaseOrder()->firstOrFail()->supplier_id,
            $document instanceof Shipment => $document->salesOrder()->firstOrFail()->customer_id,
            default => throw new InvalidArgumentException('Unsupported document for document rules.'),
        };
    }

    /**
     * Determine if the open orders of the customer plus this order exceed the customer credit limit.
     */
    protected function exceedsCreditLimit(Model $document): bool
    {
        if (! $document instanceof SalesOrder) {
            throw new InvalidArgumentException('Only sales orders have a customer credit limit.');
        }

        $customer = Customer::query()->findOrFail($document->customer_id);

        if ($customer->credit_limit === null) {
            return false;
        }

        $openOrders = SalesOrder::query()
            ->where('team_id', $document->team_id)
            ->where('customer_id', $customer->id)
            ->whereKeyNot($document->id)
            ->whereIn('status', [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped])
            ->get(['total_amount', 'exchange_rate'])
            ->sum(fn (SalesOrder $order) => (float) $order->total_amount * (float) $order->exchange_rate);

        return $openOrders + $this->totalInBaseCurrency($document) > (float) $customer->credit_limit;
    }
}
