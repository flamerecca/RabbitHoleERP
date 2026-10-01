<?php

namespace App\Actions\Products;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class DeleteUnit
{
    public function __construct(protected UnitUsage $unitUsage) {}

    /**
     * Delete a unit that is not in use and is not the reference unit of a category with other units.
     */
    public function handle(Unit $unit): void
    {
        DB::transaction(function () use ($unit) {
            abort_if($this->unitUsage->isUsed($unit), 409, __('The unit is in use and cannot be deleted.'));
            abort_if(
                $unit->is_reference && Unit::query()->where('category_id', $unit->category_id)->whereKeyNot($unit->id)->exists(),
                409,
                __('The reference unit cannot be deleted while its category has other units.'),
            );

            $unit->delete();
        });
    }
}
