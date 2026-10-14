<?php

namespace App\Http\Controllers;

use App\Actions\Purchasing\AddPurchaseOrderItem;
use App\Actions\Purchasing\CancelPurchaseOrder;
use App\Actions\Purchasing\ConfirmPurchaseOrder;
use App\Actions\Purchasing\CreatePurchaseOrder;
use App\Actions\Purchasing\UpdatePurchaseOrder;
use App\Data\DocumentResult;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\AddPurchaseOrderItemRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    /**
     * List the purchase orders of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(PurchaseOrderStatus::class)],
            'supplier_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $orders = $team->purchaseOrders()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['supplier_id'] ?? null, fn ($query, int|string $id) => $query->where('supplier_id', $id))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->where('warehouse_id', $id))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return PurchaseOrderResource::collection($orders);
    }

    /**
     * Create a draft purchase order.
     */
    public function store(StorePurchaseOrderRequest $request, Team $team, CreatePurchaseOrder $createPurchaseOrder): JsonResponse
    {
        return $this->respond($createPurchaseOrder->handle($team, $request->user(), $request->validated()), 201);
    }

    /**
     * Show the purchase order.
     */
    public function show(Team $team, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($purchaseOrder->load('items'));
    }

    /**
     * Update the draft purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, Team $team, PurchaseOrder $purchaseOrder, UpdatePurchaseOrder $updatePurchaseOrder): JsonResponse
    {
        return $this->respond($updatePurchaseOrder->handle($purchaseOrder, $request->validated()));
    }

    /**
     * Add a line to the purchase order.
     */
    public function addItem(AddPurchaseOrderItemRequest $request, Team $team, PurchaseOrder $purchaseOrder, AddPurchaseOrderItem $addPurchaseOrderItem): JsonResponse
    {
        return $this->respond($addPurchaseOrderItem->handle($purchaseOrder, $request->validated()), 201);
    }

    /**
     * Copy the purchase order into a new draft dated today.
     *
     * The number is generated here on purpose instead of by DocumentSequence, see
     * docs/architecture/document-customization.md section 6.1.
     */
    public function copy(Request $request, Team $team, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $copy = DB::transaction(function () use ($request, $team, $purchaseOrder) {
            $prefix = 'PO'.$purchaseOrder->order_date->format('Ym').'/';
            $lastOrderNo = DB::table('purchase_orders')
                ->where('team_id', $team->id)
                ->where('order_no', 'like', $prefix.'%')
                ->orderByDesc('order_no')
                ->value('order_no');
            $sequence = $lastOrderNo === null ? 1 : ((int) substr($lastOrderNo, strlen($prefix))) + 1;
            while (DB::table('purchase_orders')->where('team_id', $team->id)->where('order_no', $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT))->exists()) {
                $sequence++;
            }

            $copy = PurchaseOrder::create([
                'team_id' => $team->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'currency_id' => $purchaseOrder->currency_id,
                'exchange_rate' => $purchaseOrder->exchange_rate,
                'order_no' => $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                'status' => PurchaseOrderStatus::Draft,
                'order_date' => today(),
                'created_by' => $request->user()->id,
            ]);

            foreach ($purchaseOrder->items as $item) {
                $copy->items()->create([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate_id' => $item->tax_rate_id,
                    'received_quantity' => 0,
                ]);
            }

            $copy->recalculateTotals();

            return $copy;
        });

        return new PurchaseOrderResource($copy->fresh('items'))->response()->setStatusCode(201);
    }

    /**
     * Confirm the draft purchase order.
     */
    public function confirm(Team $team, PurchaseOrder $purchaseOrder, ConfirmPurchaseOrder $confirmPurchaseOrder): JsonResponse
    {
        return $this->respond($confirmPurchaseOrder->handle($purchaseOrder));
    }

    /**
     * Cancel the purchase order.
     */
    public function cancel(Team $team, PurchaseOrder $purchaseOrder, CancelPurchaseOrder $cancelPurchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($cancelPurchaseOrder->handle($purchaseOrder));
    }

    /**
     * Respond with the purchase order and the warnings of the matched document rules.
     *
     * @param  DocumentResult<PurchaseOrder>  $result
     */
    protected function respond(DocumentResult $result, int $status = 200): JsonResponse
    {
        return new PurchaseOrderResource($result->document)
            ->additional(['meta' => ['warnings' => $result->warnings]])
            ->response()
            ->setStatusCode($status);
    }
}
