<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\DeleteCurrency;
use App\Actions\MasterData\SaveCurrency;
use App\Http\Requests\SaveCurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * List the currencies of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_base' => ['nullable', Rule::in(['true', 'false', '1', '0'])],
        ]);

        $records = $team->currencies()
            ->when(array_key_exists('is_base', $validated), fn ($query) => $query->where('is_base', $request->boolean('is_base')))
            ->orderBy('code')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return CurrencyResource::collection($records);
    }

    /**
     * Create a currency.
     */
    public function store(SaveCurrencyRequest $request, Team $team, SaveCurrency $save): JsonResponse
    {
        return new CurrencyResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the currency.
     */
    public function show(Team $team, Currency $currency): CurrencyResource
    {
        return new CurrencyResource($currency);
    }

    /**
     * Update the given fields of the currency.
     */
    public function update(SaveCurrencyRequest $request, Team $team, Currency $currency, SaveCurrency $save): CurrencyResource
    {
        return new CurrencyResource($save->handle($team, $request->validated(), $currency));
    }

    /**
     * Delete the currency when nothing refers to it.
     */
    public function destroy(Team $team, Currency $currency, DeleteCurrency $delete): Response
    {
        $delete->handle($currency);

        return response()->noContent();
    }
}
