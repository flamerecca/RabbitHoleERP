<?php

namespace App\Http\Controllers;

use App\Actions\Sales\CreateConsignmentHold;
use App\Actions\Sales\MarkConsignmentHoldPickedUp;
use App\Enums\ConsignmentHoldStatus;
use App\Http\Requests\StoreConsignmentHoldRequest;
use App\Http\Resources\ConsignmentHoldResource;
use App\Models\ConsignmentHold;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ConsignmentHoldController extends Controller
{
    /**
     * List the consignment holds of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(ConsignmentHoldStatus::class)],
            'shipment_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $holds = $team->consignmentHolds()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['shipment_id'] ?? null, fn ($query, int|string $id) => $query->where('shipment_id', $id))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->where('warehouse_id', $id))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ConsignmentHoldResource::collection($holds);
    }

    /**
     * Hold the goods of a confirmed shipment for the customer.
     */
    public function store(StoreConsignmentHoldRequest $request, Team $team, CreateConsignmentHold $create): JsonResponse
    {
        return new ConsignmentHoldResource($create->handle($team, $request->user(), $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the consignment hold.
     */
    public function show(Team $team, ConsignmentHold $consignmentHold): ConsignmentHoldResource
    {
        return new ConsignmentHoldResource($consignmentHold);
    }

    /**
     * Record that the customer picked up the held goods.
     */
    public function pickup(Team $team, ConsignmentHold $consignmentHold, MarkConsignmentHoldPickedUp $markPickedUp): ConsignmentHoldResource
    {
        return new ConsignmentHoldResource($markPickedUp->handle($consignmentHold));
    }
}
