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
