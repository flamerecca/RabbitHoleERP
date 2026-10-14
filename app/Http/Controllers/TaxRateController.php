<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\DeleteTaxRate;
use App\Actions\MasterData\SaveTaxRate;
use App\Http\Requests\SaveTaxRateRequest;
use App\Http\Resources\TaxRateResource;
use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaxRateController extends Controller
{
    /**
     * List the tax rates of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $records = $team->taxRates()
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return TaxRateResource::collection($records);
    }

    /**
     * Create a tax rate.
     */
    public function store(SaveTaxRateRequest $request, Team $team, SaveTaxRate $save): JsonResponse
    {
        return new TaxRateResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the tax rate.
     */
    public function show(Team $team, TaxRate $taxRate): TaxRateResource
    {
        return new TaxRateResource($taxRate);
    }

    /**
     * Update the given fields of the tax rate.
     */
    public function update(SaveTaxRateRequest $request, Team $team, TaxRate $taxRate, SaveTaxRate $save): TaxRateResource
    {
        return new TaxRateResource($save->handle($team, $request->validated(), $taxRate));
    }

    /**
     * Delete the tax rate when nothing refers to it.
     */
    public function destroy(Team $team, TaxRate $taxRate, DeleteTaxRate $delete): Response
    {
        $delete->handle($taxRate);

        return response()->noContent();
    }
}
