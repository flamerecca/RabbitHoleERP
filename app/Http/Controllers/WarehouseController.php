<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\DeleteWarehouse;
use App\Actions\MasterData\SaveWarehouse;
use App\Http\Requests\SaveWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WarehouseController extends Controller
{
    /**
     * List the warehouses of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $records = $team->warehouses()
            ->orderBy('code')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return WarehouseResource::collection($records);
    }

    /**
     * Create a warehouse.
     */
    public function store(SaveWarehouseRequest $request, Team $team, SaveWarehouse $save): JsonResponse
    {
        return new WarehouseResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the warehouse.
     */
    public function show(Team $team, Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource($warehouse);
    }

    /**
     * Update the given fields of the warehouse.
     */
    public function update(SaveWarehouseRequest $request, Team $team, Warehouse $warehouse, SaveWarehouse $save): WarehouseResource
    {
        return new WarehouseResource($save->handle($team, $request->validated(), $warehouse));
    }

    /**
     * Delete the warehouse when nothing refers to it.
     */
    public function destroy(Team $team, Warehouse $warehouse, DeleteWarehouse $delete): Response
    {
        $delete->handle($warehouse);

        return response()->noContent();
    }
}
