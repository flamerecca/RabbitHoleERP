<?php

namespace App\Http\Controllers;

use App\Actions\Purchasing\CancelPurchaseReturn;
use App\Actions\Purchasing\ConfirmPurchaseReturn;
use App\Actions\Purchasing\CreatePurchaseReturn;
use App\Actions\Purchasing\UpdatePurchaseReturn;
use App\Enums\DocumentStatus;
use App\Http\Requests\StorePurchaseReturnRequest;
use App\Http\Requests\UpdatePurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PurchaseReturnController extends Controller
{
    /**
     * List the purchase returns of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'supplier_id' => ['nullable', 'integer'],
            'goods_receipt_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->purchaseReturns()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, int|string $value) => $query->where('status', $value))
            ->when($validated['supplier_id'] ?? null, fn ($query, int|string $value) => $query->where('supplier_id', $value))
            ->when($validated['goods_receipt_id'] ?? null, fn ($query, int|string $value) => $query->where('goods_receipt_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return PurchaseReturnResource::collection($documents);
    }

    /**
     * Create a draft purchase return.
     */
    public function store(StorePurchaseReturnRequest $request, Team $team, CreatePurchaseReturn $create): JsonResponse
    {
        return new PurchaseReturnResource($create->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the purchase return.
     */
    public function show(Team $team, PurchaseReturn $purchaseReturn): PurchaseReturnResource
    {
        return new PurchaseReturnResource($purchaseReturn->load('items'));
    }

    /**
     * Update the draft purchase return.
     */
    public function update(UpdatePurchaseReturnRequest $request, Team $team, PurchaseReturn $purchaseReturn, UpdatePurchaseReturn $update): PurchaseReturnResource
    {
        return new PurchaseReturnResource($update->handle($purchaseReturn, $request->validated()));
    }

    /**
     * Confirm the draft purchase return.
     */
    public function confirm(Request $request, Team $team, PurchaseReturn $purchaseReturn, ConfirmPurchaseReturn $confirm): PurchaseReturnResource
    {
        return new PurchaseReturnResource($confirm->handle($purchaseReturn, $request->user()));
    }

    /**
     * Cancel the draft purchase return.
     */
    public function cancel(Team $team, PurchaseReturn $purchaseReturn, CancelPurchaseReturn $cancel): PurchaseReturnResource
    {
        return new PurchaseReturnResource($cancel->handle($purchaseReturn));
    }
}
