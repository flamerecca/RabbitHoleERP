<?php

namespace App\Actions\Products;

use App\Models\Team;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class CreateUnit
{
    /**
     * Create a unit; the first unit of a category becomes its reference unit with a ratio of 1.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes): Unit
    {
        return DB::transaction(function () use ($team, $attributes) {
            $isReference = Unit::query()->where('category_id', $attributes['category_id'])->lockForUpdate()->doesntExist();

            return Unit::create([
                'is_active' => true,
                ...$attributes,
                'team_id' => $team->id,
                'is_reference' => $isReference,
                'ratio' => $isReference ? 1 : $attributes['ratio'],
            ]);
        });
    }
}
