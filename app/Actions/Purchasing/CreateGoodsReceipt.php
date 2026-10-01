<?php

namespace App\Actions\Purchasing;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentRuleEvaluator;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReceipt
{
    public function __construct(
        protected DocumentSequence $documentSequence,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Create a draft goods receipt for lines of a purchase order and check the creation rules.
     *
     * @param  array<string, mixed>  $attributes
     * @return DocumentResult<GoodsReceipt>
     *
     * @throws ValidationException
     */
    public function handle(Team $team, User $user, array $attributes): DocumentResult
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $order = PurchaseOrder::query()->whereKey($attributes['purchase_order_id'])->lockForUpdate()->firstOrFail();

            abort_unless(in_array($order->status, [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived], true), 409, __('The purchase order does not allow receiving goods.'));

            $document = GoodsReceipt::create([
                'team_id' => $team->id,
                'purchase_order_id' => $order->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'receipt_no' => $this->documentSequence->next($team, DocumentType::GoodsReceipt, CarbonImmutable::parse($attributes['received_date'])),
                'received_date' => $attributes['received_date'],
                'status' => DocumentStatus::Draft,
                'created_by' => $user->id,
            ]);

            $this->createLines($document, $order, $attributes['items']);

            $warnings = $this->documentRuleEvaluator->ensurePasses($team, DocumentType::GoodsReceipt, DocumentRuleEvent::Create, $document);

            return new DocumentResult($document->fresh('items'), $warnings);
        });
    }

    /**
     * Create the lines of the goods receipt, each pointing at a line of its purchase order with quantity still open.
     *
     * @param  list<array<string, mixed>>  $lines
     *
     * @throws ValidationException
     */
    public function createLines(GoodsReceipt $document, PurchaseOrder $order, array $lines): void
    {
        $orderItems = $order->items()->with('product')->get()->keyBy('id');
        $requested = [];

        foreach ($lines as $index => $line) {
            $orderItem = $orderItems->get($line['purchase_order_item_id']);

            if ($orderItem === null) {
                throw ValidationException::withMessages(["items.{$index}.purchase_order_item_id" => __('The line must belong to the source order.')]);
            }

            $requested[$orderItem->id] = ($requested[$orderItem->id] ?? 0.0) + (float) $line['quantity'];

            if (round($requested[$orderItem->id], 4) > round((float) $orderItem->quantity - (float) $orderItem->received_quantity, 4)) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => __('The received quantity exceeds the quantity still to receive.')]);
            }

            $lotNo = filled($line['lot_no'] ?? null) ? (string) $line['lot_no'] : null;

            if ($orderItem->product->isLotTracked() !== ($lotNo !== null)) {
                throw ValidationException::withMessages(["items.{$index}.lot_no" => $orderItem->product->isLotTracked()
                    ? __('A lot number is required for products tracked by lot.')
                    : __('Only products tracked by lot take a lot number.')]);
            }

            $document->items()->create([
                'purchase_order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'quantity' => $line['quantity'],
                'unit_cost' => $line['unit_cost'],
                'lot_no' => $lotNo,
            ]);
        }
    }
}
