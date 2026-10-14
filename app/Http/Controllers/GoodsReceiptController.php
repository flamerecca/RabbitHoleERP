<?php

namespace App\Http\Controllers;

use App\Actions\Purchasing\CancelGoodsReceipt;
use App\Actions\Purchasing\CreateGoodsReceipt;
use App\Actions\Purchasing\ReceiveGoods;
use App\Actions\Purchasing\UpdateGoodsReceipt;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Http\Requests\UpdateGoodsReceiptRequest;
use App\Http\Resources\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class GoodsReceiptController extends Controller
{
    /**
     * List the documents of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'purchase_order_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->goodsReceipts()
            ->with(['items', 'purchaseOrder'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['purchase_order_id'] ?? null, fn ($query, int|string $id) => $query->where('purchase_order_id', $id))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->where('warehouse_id', $id))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return GoodsReceiptResource::collection($documents);
    }

    /**
     * Create a draft document.
     */
    public function store(StoreGoodsReceiptRequest $request, Team $team, CreateGoodsReceipt $create): JsonResponse
    {
        return $this->respond($create->handle($team, $request->user(), $request->validated()), 201);
    }

    /**
     * Show the document.
     */
    public function show(Team $team, GoodsReceipt $goodsReceipt): GoodsReceiptResource
    {
        return new GoodsReceiptResource($goodsReceipt->load('items'));
    }

    /**
     * Update the draft document.
     */
    public function update(UpdateGoodsReceiptRequest $request, Team $team, GoodsReceipt $goodsReceipt, UpdateGoodsReceipt $update): JsonResponse
    {
        return $this->respond($update->handle($goodsReceipt, $request->validated()));
    }

    /**
     * Confirm the draft document.
     */
    public function confirm(Request $request, Team $team, GoodsReceipt $goodsReceipt, ReceiveGoods $receiveGoods): JsonResponse
    {
        return $this->respond($receiveGoods->handle($goodsReceipt, $request->user()));
    }

    /**
     * Cancel the draft document.
     */
    public function cancel(Team $team, GoodsReceipt $goodsReceipt, CancelGoodsReceipt $cancel): GoodsReceiptResource
    {
        return new GoodsReceiptResource($cancel->handle($goodsReceipt));
    }

    /**
     * Respond with the document and the warnings of the matched document rules.
     *
     * @param  DocumentResult<GoodsReceipt>  $result
     */
    protected function respond(DocumentResult $result, int $status = 200): JsonResponse
    {
        return new GoodsReceiptResource($result->document)
            ->additional(['meta' => ['warnings' => $result->warnings]])
            ->response()
            ->setStatusCode($status);
    }
}
