<?php

namespace App\Actions\Sales;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\StockLot;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentRuleEvaluator;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateShipment
{
    public function __construct(
        protected DocumentSequence $documentSequence,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Create a draft shipment for lines of a sales order and check the creation rules.
     *
     * @param  array<string, mixed>  $attributes
     * @return DocumentResult<Shipment>
     *
     * @throws ValidationException
     */
    public function handle(Team $team, User $user, array $attributes): DocumentResult
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $order = SalesOrder::query()->whereKey($attributes['sales_order_id'])->lockForUpdate()->firstOrFail();

            abort_unless(in_array($order->status, [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped], true), 409, __('The sales order does not allow shipping.'));

            $document = Shipment::create([
                'team_id' => $team->id,
                'sales_order_id' => $order->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'shipment_no' => $this->documentSequence->next($team, DocumentType::Shipment, CarbonImmutable::parse($attributes['shipped_date'])),
                'shipped_date' => $attributes['shipped_date'],
                'status' => DocumentStatus::Draft,
                'created_by' => $user->id,
            ]);

            $this->createLines($document, $order, $attributes['items']);

            $warnings = $this->documentRuleEvaluator->ensurePasses($team, DocumentType::Shipment, DocumentRuleEvent::Create, $document);

            return new DocumentResult($document->fresh('items'), $warnings);
        });
    }

    /**
     * Create the lines of the shipment, each pointing at a line of its sales order with quantity still open.
     *
     * @param  list<array<string, mixed>>  $lines
     *
     * @throws ValidationException
     */
    public function createLines(Shipment $document, SalesOrder $order, array $lines): void
    {
        $orderItems = $order->items()->get()->keyBy('id');
        $requested = [];

        foreach ($lines as $index => $line) {
            $orderItem = $orderItems->get($line['sales_order_item_id']);

            if ($orderItem === null) {
                throw ValidationException::withMessages(["items.{$index}.sales_order_item_id" => __('The line must belong to the source order.')]);
            }

            $requested[$orderItem->id] = ($requested[$orderItem->id] ?? 0.0) + (float) $line['quantity'];

            if (round($requested[$orderItem->id], 4) > round((float) $orderItem->quantity - (float) $orderItem->shipped_quantity, 4)) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => __('The shipped quantity exceeds the quantity still to ship.')]);
            }

            $stockLotId = $line['stock_lot_id'] ?? null;

            if ($stockLotId !== null && ! StockLot::query()->whereKey($stockLotId)->where('product_id', $orderItem->product_id)->exists()) {
                throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('The lot must belong to the product of the line.')]);
            }

            $document->items()->create([
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'quantity' => $line['quantity'],
                'stock_lot_id' => $stockLotId,
            ]);
        }
    }
}
