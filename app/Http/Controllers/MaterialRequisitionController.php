<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\CancelMaterialRequisition;
use App\Actions\Inventory\ConfirmMaterialRequisition;
use App\Actions\Inventory\CreateMaterialRequisition;
use App\Actions\Inventory\UpdateMaterialRequisition;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreMaterialRequisitionRequest;
use App\Http\Requests\UpdateMaterialRequisitionRequest;
use App\Http\Resources\MaterialRequisitionResource;
use App\Models\MaterialRequisition;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MaterialRequisitionController extends Controller
{
    /**
     * List the material requisitions of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->materialRequisitions()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, int|string $value) => $query->where('status', $value))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $value) => $query->where('warehouse_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return MaterialRequisitionResource::collection($documents);
    }

    /**
     * Create a draft material requisition.
     */
    public function store(StoreMaterialRequisitionRequest $request, Team $team, CreateMaterialRequisition $create): JsonResponse
    {
        return new MaterialRequisitionResource($create->handle($team, $request->user(), $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the material requisition.
     */
    public function show(Team $team, MaterialRequisition $materialRequisition): MaterialRequisitionResource
    {
        return new MaterialRequisitionResource($materialRequisition->load('items'));
    }

    /**
     * Update the draft material requisition.
     */
    public function update(UpdateMaterialRequisitionRequest $request, Team $team, MaterialRequisition $materialRequisition, UpdateMaterialRequisition $update): MaterialRequisitionResource
    {
        return new MaterialRequisitionResource($update->handle($materialRequisition, $request->validated()));
    }

    /**
     * Confirm the draft material requisition.
     */
    public function confirm(Request $request, Team $team, MaterialRequisition $materialRequisition, ConfirmMaterialRequisition $confirm): MaterialRequisitionResource
    {
        return new MaterialRequisitionResource($confirm->handle($materialRequisition, $request->user()));
    }

    /**
     * Cancel the draft material requisition.
     */
    public function cancel(Team $team, MaterialRequisition $materialRequisition, CancelMaterialRequisition $cancel): MaterialRequisitionResource
    {
        return new MaterialRequisitionResource($cancel->handle($materialRequisition));
    }
}
