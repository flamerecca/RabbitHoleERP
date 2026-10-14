<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\CancelStockTake;
use App\Actions\Inventory\CreateStockTake;
use App\Actions\Inventory\ReconcileStockTake;
use App\Actions\Inventory\UpdateStockTake;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreStockTakeRequest;
use App\Http\Requests\UpdateStockTakeRequest;
use App\Http\Resources\StockTakeResource;
use App\Models\StockTake;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StockTakeController extends Controller
{
    /**
     * List the stock takes of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->stockTakes()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, int|string $value) => $query->where('status', $value))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $value) => $query->where('warehouse_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return StockTakeResource::collection($documents);
    }

    /**
     * Create a draft stock take.
     */
    public function store(StoreStockTakeRequest $request, Team $team, CreateStockTake $create): JsonResponse
    {
        return new StockTakeResource($create->handle($team, $request->user(), $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the stock take.
     */
    public function show(Team $team, StockTake $stockTake): StockTakeResource
    {
        return new StockTakeResource($stockTake->load('items'));
    }

    /**
     * Update the draft stock take.
     */
    public function update(UpdateStockTakeRequest $request, Team $team, StockTake $stockTake, UpdateStockTake $update): StockTakeResource
    {
        return new StockTakeResource($update->handle($stockTake, $request->validated()));
    }

    /**
     * Confirm the draft stock take.
     */
    public function confirm(Request $request, Team $team, StockTake $stockTake, ReconcileStockTake $confirm): StockTakeResource
    {
        return new StockTakeResource($confirm->handle($stockTake, $request->user()));
    }

    /**
     * Cancel the draft stock take.
     */
    public function cancel(Team $team, StockTake $stockTake, CancelStockTake $cancel): StockTakeResource
    {
        return new StockTakeResource($cancel->handle($stockTake));
    }
}
