<?php

namespace App\Http\Controllers;

use App\Actions\Sales\CancelSalesReturn;
use App\Actions\Sales\ConfirmSalesReturn;
use App\Actions\Sales\CreateSalesReturn;
use App\Actions\Sales\UpdateSalesReturn;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreSalesReturnRequest;
use App\Http\Requests\UpdateSalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Models\SalesReturn;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SalesReturnController extends Controller
{
    /**
     * List the sales returns of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'customer_id' => ['nullable', 'integer'],
            'shipment_id' => ['nullable', 'integer'],
        ]);

        $documents = $team->salesReturns()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, int|string $value) => $query->where('status', $value))
            ->when($validated['customer_id'] ?? null, fn ($query, int|string $value) => $query->where('customer_id', $value))
            ->when($validated['shipment_id'] ?? null, fn ($query, int|string $value) => $query->where('shipment_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return SalesReturnResource::collection($documents);
    }

    /**
     * Create a draft sales return.
     */
    public function store(StoreSalesReturnRequest $request, Team $team, CreateSalesReturn $create): JsonResponse
    {
        return new SalesReturnResource($create->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the sales return.
     */
    public function show(Team $team, SalesReturn $salesReturn): SalesReturnResource
    {
        return new SalesReturnResource($salesReturn->load('items'));
    }

    /**
     * Update the draft sales return.
     */
    public function update(UpdateSalesReturnRequest $request, Team $team, SalesReturn $salesReturn, UpdateSalesReturn $update): SalesReturnResource
    {
        return new SalesReturnResource($update->handle($salesReturn, $request->validated()));
    }

    /**
     * Confirm the draft sales return.
     */
    public function confirm(Request $request, Team $team, SalesReturn $salesReturn, ConfirmSalesReturn $confirm): SalesReturnResource
    {
        return new SalesReturnResource($confirm->handle($salesReturn, $request->user()));
    }

    /**
     * Cancel the draft sales return.
     */
    public function cancel(Team $team, SalesReturn $salesReturn, CancelSalesReturn $cancel): SalesReturnResource
    {
        return new SalesReturnResource($cancel->handle($salesReturn));
    }
}
