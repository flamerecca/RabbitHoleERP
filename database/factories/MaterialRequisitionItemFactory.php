<?php

namespace Database\Factories;

use App\Models\MaterialRequisition;
use App\Models\MaterialRequisitionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialRequisitionItem>
 */
class MaterialRequisitionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_requisition_id' => MaterialRequisition::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'note' => null,
        ];
    }
}
