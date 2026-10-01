<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\DeleteSupplier;
use App\Actions\MasterData\SaveSupplier;
use App\Http\Requests\SaveSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    /**
     * List the suppliers of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $records = $team->suppliers()
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('code')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return SupplierResource::collection($records);
    }

    /**
     * Create a supplier.
     */
    public function store(SaveSupplierRequest $request, Team $team, SaveSupplier $save): JsonResponse
    {
        return new SupplierResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the supplier.
     */
    public function show(Team $team, Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }

    /**
     * Update the given fields of the supplier.
     */
    public function update(SaveSupplierRequest $request, Team $team, Supplier $supplier, SaveSupplier $save): SupplierResource
    {
        return new SupplierResource($save->handle($team, $request->validated(), $supplier));
    }

    /**
     * Delete the supplier when nothing refers to it.
     */
    public function destroy(Team $team, Supplier $supplier, DeleteSupplier $delete): Response
    {
        $delete->handle($supplier);

        return response()->noContent();
    }
}
