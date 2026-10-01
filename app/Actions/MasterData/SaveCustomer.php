<?php

namespace App\Actions\MasterData;

use App\Models\Customer;
use App\Models\Team;

class SaveCustomer
{
    /**
     * Create or update a customer.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes, ?Customer $customer = null): Customer
    {
        $customer ??= new Customer(['team_id' => $team->id]);
        $customer->fill($attributes)->save();

        return $customer;
    }
}
