<?php

namespace App\Actions\MasterData;

use App\Models\Supplier;
use App\Models\Team;

class SaveSupplier
{
    /**
     * Create or update a supplier.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes, ?Supplier $supplier = null): Supplier
    {
        $supplier ??= new Supplier(['team_id' => $team->id]);
        $supplier->fill($attributes)->save();

        return $supplier;
    }
}
