<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Resources\StockBalanceResource;
use App\Http\Resources\StockMovementResource;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    /**
     * List the stock balances of the team.
     */
    public function balances(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
        ]);

        $balances = $team->stockBalances()
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->where('warehouse_id', $id))
            ->when($validated['product_id'] ?? null, fn ($query, int|string $id) => $query->where('product_id', $id))
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return StockBalanceResource::collection($balances);
    }

    /**
     * Show a stock balance.
     */
    public function balance(Team $team, StockBalance $stockBalance): StockBalanceResource
    {
        return new StockBalanceResource($stockBalance);
    }

    /**
     * List the stock movements of the team, newest first.
     */
    public function movements(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'reference_type' => ['nullable', 'string', 'max:50'],
        ]);

        $movements = $team->stockMovements()
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->where('warehouse_id', $id))
            ->when($validated['product_id'] ?? null, fn ($query, int|string $id) => $query->where('product_id', $id))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($validated['reference_type'] ?? null, fn ($query, string $type) => $query->where('reference_type', $type))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return StockMovementResource::collection($movements);
    }

    /**
     * Show a stock movement.
     */
    public function movement(Team $team, StockMovement $stockMovement): StockMovementResource
    {
        return new StockMovementResource($stockMovement);
    }
}
