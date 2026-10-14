<?php

namespace App\Http\Controllers;

use App\Actions\Accounting\DeleteChartOfAccount;
use App\Actions\Accounting\SaveChartOfAccount;
use App\Enums\AccountType;
use App\Http\Requests\SaveChartOfAccountRequest;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    /**
     * List the chart of accounts of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'account_type' => ['nullable', Rule::enum(AccountType::class)],
            'is_active' => ['nullable', Rule::in(['true', 'false', '1', '0'])],
        ]);

        $accounts = $team->chartOfAccounts()
            ->when($validated['account_type'] ?? null, fn ($query, string $value) => $query->where('account_type', $value))
            ->when(array_key_exists('is_active', $validated), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('code')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ChartOfAccountResource::collection($accounts);
    }

    /**
     * Create an account.
     */
    public function store(SaveChartOfAccountRequest $request, Team $team, SaveChartOfAccount $save): JsonResponse
    {
        return new ChartOfAccountResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the account.
     */
    public function show(Team $team, ChartOfAccount $chartOfAccount): ChartOfAccountResource
    {
        return new ChartOfAccountResource($chartOfAccount);
    }

    /**
     * Update the given fields of the account.
     */
    public function update(SaveChartOfAccountRequest $request, Team $team, ChartOfAccount $chartOfAccount, SaveChartOfAccount $save): ChartOfAccountResource
    {
        return new ChartOfAccountResource($save->handle($team, $request->validated(), $chartOfAccount));
    }

    /**
     * Delete the account when nothing refers to it.
     */
    public function destroy(Team $team, ChartOfAccount $chartOfAccount, DeleteChartOfAccount $delete): Response
    {
        $delete->handle($chartOfAccount);

        return response()->noContent();
    }
}
