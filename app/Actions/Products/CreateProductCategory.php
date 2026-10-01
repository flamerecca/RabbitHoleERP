<?php

namespace App\Actions\Products;

use App\Enums\CostMethod;
use App\Models\ProductCategory;
use App\Models\Team;

class CreateProductCategory
{
    /**
     * Create a product category, defaulting to the average cost method.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes): ProductCategory
    {
        return ProductCategory::create([
            'cost_method' => CostMethod::Average,
            ...$attributes,
            'team_id' => $team->id,
        ]);
    }
}
