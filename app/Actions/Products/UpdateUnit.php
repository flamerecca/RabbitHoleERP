<?php

namespace App\Actions\Products;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UpdateUnit
{
    public function __construct(protected UnitUsage $unitUsage) {}

    /**
     * Update a unit, refusing to change the category or ratio of a unit in use or of a reference unit with siblings.
     * The rules compare against a freshly locked copy, because callers such as Filament may already have filled the given model.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Unit $unit, array $attributes): Unit
    {
        return DB::transaction(function () use ($unit, $attributes) {
            $unit = Unit::query()->whereKey($unit->getKey())->lockForUpdate()->firstOrFail();
            $changesCategory = array_key_exists('category_id', $attributes) && (int) $attributes['category_id'] !== $unit->category_id;
            $changesRatio = array_key_exists('ratio', $attributes) && round((float) $attributes['ratio'], 8) !== round((float) $unit->ratio, 8);

            if ($changesCategory || $changesRatio) {
                abort_if($this->unitUsage->isUsed($unit), 409, __('The unit is in use, so its category and ratio cannot change.'));
                abort_if($unit->is_reference, 409, __('The category and ratio of a reference unit cannot change.'));
            }

            $unit->update($attributes);

            return $unit;
        });
    }
}
