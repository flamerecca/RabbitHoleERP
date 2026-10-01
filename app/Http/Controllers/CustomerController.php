<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\DeleteCustomer;
use App\Actions\MasterData\SaveCustomer;
use App\Http\Requests\SaveCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    /**
     * List the customers of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $records = $team->customers()
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('code')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return CustomerResource::collection($records);
    }

    /**
     * Create a customer.
     */
    public function store(SaveCustomerRequest $request, Team $team, SaveCustomer $save): JsonResponse
    {
        return new CustomerResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the customer.
     */
    public function show(Team $team, Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    /**
     * Update the given fields of the customer.
     */
    public function update(SaveCustomerRequest $request, Team $team, Customer $customer, SaveCustomer $save): CustomerResource
    {
        return new CustomerResource($save->handle($team, $request->validated(), $customer));
    }

    /**
     * Delete the customer when nothing refers to it.
     */
    public function destroy(Team $team, Customer $customer, DeleteCustomer $delete): Response
    {
        $delete->handle($customer);

        return response()->noContent();
    }
}
