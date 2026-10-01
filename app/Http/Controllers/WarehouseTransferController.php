<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\CancelWarehouseTransfer;
use App\Actions\Inventory\CreateWarehouseTransfer;
use App\Actions\Inventory\TransferStock;
use App\Actions\Inventory\UpdateWarehouseTransfer;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreWarehouseTransferRequest;
use App\Http\Requests\UpdateWarehouseTransferRequest;
use App\Http\Resources\WarehouseTransferResource;
use App\Models\Team;
use App\Models\WarehouseTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WarehouseTransferController extends Controller
{
    /**
     * List the warehouse transfers of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'from_warehouse_id' => ['nullable', 'integer'],
            'to_warehouse_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->warehouseTransfers()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, int|string $value) => $query->where('status', $value))
            ->when($validated['from_warehouse_id'] ?? null, fn ($query, int|string $value) => $query->where('from_warehouse_id', $value))
            ->when($validated['to_warehouse_id'] ?? null, fn ($query, int|string $value) => $query->where('to_warehouse_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return WarehouseTransferResource::collection($documents);
    }

    /**
     * Create a draft warehouse transfer.
     */
    public function store(StoreWarehouseTransferRequest $request, Team $team, CreateWarehouseTransfer $create): JsonResponse
    {
        return new WarehouseTransferResource($create->handle($team, $request->user(), $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the warehouse transfer.
     */
    public function show(Team $team, WarehouseTransfer $warehouseTransfer): WarehouseTransferResource
    {
        return new WarehouseTransferResource($warehouseTransfer->load('items'));
    }

    /**
     * Update the draft warehouse transfer.
     */
    public function update(UpdateWarehouseTransferRequest $request, Team $team, WarehouseTransfer $warehouseTransfer, UpdateWarehouseTransfer $update): WarehouseTransferResource
    {
        return new WarehouseTransferResource($update->handle($warehouseTransfer, $request->validated()));
    }

    /**
     * Confirm the draft warehouse transfer.
     */
    public function confirm(Request $request, Team $team, WarehouseTransfer $warehouseTransfer, TransferStock $confirm): WarehouseTransferResource
    {
        return new WarehouseTransferResource($confirm->handle($warehouseTransfer, $request->user()));
    }

    /**
     * Cancel the draft warehouse transfer.
     */
    public function cancel(Team $team, WarehouseTransfer $warehouseTransfer, CancelWarehouseTransfer $cancel): WarehouseTransferResource
    {
        return new WarehouseTransferResource($cancel->handle($warehouseTransfer));
    }
}
