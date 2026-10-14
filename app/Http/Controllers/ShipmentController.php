<?php

namespace App\Http\Controllers;

use App\Actions\Sales\CancelShipment;
use App\Actions\Sales\CreateShipment;
use App\Actions\Sales\ShipSalesOrder;
use App\Actions\Sales\UpdateShipment;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
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
            'sales_order_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->shipments()
            ->with(['items', 'salesOrder'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['sales_order_id'] ?? null, fn ($query, int|string $id) => $query->where('sales_order_id', $id))
            ->when($validated['warehouse_id'] ?? null, fn ($query, int|string $id) => $query->where('warehouse_id', $id))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ShipmentResource::collection($documents);
    }

    /**
     * Create a draft document.
     */
    public function store(StoreShipmentRequest $request, Team $team, CreateShipment $create): JsonResponse
    {
        return $this->respond($create->handle($team, $request->user(), $request->validated()), 201);
    }

    /**
     * Show the document.
     */
    public function show(Team $team, Shipment $shipment): ShipmentResource
    {
        return new ShipmentResource($shipment->load('items'));
    }

    /**
     * Update the draft document.
     */
    public function update(UpdateShipmentRequest $request, Team $team, Shipment $shipment, UpdateShipment $update): JsonResponse
    {
        return $this->respond($update->handle($shipment, $request->validated()));
    }

    /**
     * Confirm the draft document.
     */
    public function confirm(Request $request, Team $team, Shipment $shipment, ShipSalesOrder $shipSalesOrder): JsonResponse
    {
        return $this->respond($shipSalesOrder->handle($shipment, $request->user()));
    }

    /**
     * Cancel the draft document.
     */
    public function cancel(Team $team, Shipment $shipment, CancelShipment $cancel): ShipmentResource
    {
        return new ShipmentResource($cancel->handle($shipment));
    }

    /**
     * Respond with the document and the warnings of the matched document rules.
     *
     * @param  DocumentResult<Shipment>  $result
     */
    protected function respond(DocumentResult $result, int $status = 200): JsonResponse
    {
        return (new ShipmentResource($result->document))
            ->additional(['meta' => ['warnings' => $result->warnings]])
            ->response()
            ->setStatusCode($status);
    }
}
