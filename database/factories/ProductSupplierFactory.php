<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSupplier>
 */
class ProductSupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'product_id' => Product::factory(),
            'supplier_id' => Supplier::factory(),
            'supplier_product_code' => null,
            'supplier_product_name' => null,
            'currency_id' => Currency::factory(),
            'unit_price' => 100,
            'min_quantity' => 0,
            'lead_time_days' => 0,
            'valid_from' => null,
            'valid_until' => null,
            'sequence' => 10,
        ];
    }
}
