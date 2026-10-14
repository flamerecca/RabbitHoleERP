<?php

namespace App\Actions\MasterData;

use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class SaveTaxRate
{
    /**
     * Create or update a tax rate, keeping at most one default rate per team.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes, ?TaxRate $taxRate = null): TaxRate
    {
        return DB::transaction(function () use ($team, $attributes, $taxRate) {
            $taxRate ??= new TaxRate(['team_id' => $team->id]);
            $taxRate->fill($attributes)->save();

            if ($taxRate->is_default) {
                TaxRate::query()->where('team_id', $team->id)->whereKeyNot($taxRate->id)->update(['is_default' => false]);
            }

            return $taxRate;
        });
    }
}
