<?php

namespace App\Actions\MasterData;

use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SaveWarehouse
{
    /**
     * Create or update a warehouse, keeping at most one default warehouse per team.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes, ?Warehouse $warehouse = null): Warehouse
    {
        return DB::transaction(function () use ($team, $attributes, $warehouse) {
            $warehouse ??= new Warehouse(['team_id' => $team->id]);
            $warehouse->fill($attributes)->save();

            if ($warehouse->is_default) {
                Warehouse::query()->where('team_id', $team->id)->whereKeyNot($warehouse->id)->update(['is_default' => false]);
            }

            return $warehouse;
        });
    }
}
