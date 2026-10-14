<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    /**
     * Get a page of the customers of the team ordered by code, optionally narrowed by name or code.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginateForTeam(Team $team, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $team->customers()
            ->when($search, fn ($query, string $term) => $query->where(fn ($query) => $query->whereLike('name', "%{$term}%")->orWhereLike('code', "%{$term}%")))
            ->orderBy('code')
            ->paginate($perPage);
    }
}
