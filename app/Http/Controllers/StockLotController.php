<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockLotResource;
use App\Models\StockLot;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockLotController extends Controller
{
    /**
     * List the lots of the team, newest first, with their stock in each warehouse.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'product_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $lots = $team->stockLots()
            ->with('balances')
            ->when($validated['product_id'] ?? null, fn ($query, int|string $id) => $query->where('product_id', $id))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->whereHas('balances', fn ($query) => $query->where('warehouse_id', $id)->where('quantity_on_hand', '!=', 0)))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where('lot_no', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return StockLotResource::collection($lots);
    }

    /**
     * Show a lot with its stock in each warehouse.
     */
    public function show(Team $team, StockLot $stockLot): StockLotResource
    {
        return new StockLotResource($stockLot->load('balances'));
    }
}
