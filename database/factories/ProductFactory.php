<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'category_id' => null,
            'unit_id' => Unit::factory(),
            'purchase_unit_id' => fn (array $attributes) => $attributes['unit_id'],
            'sku' => fake()->unique()->bothify('SKU-#####'),
            'name' => fake()->words(3, true),
            'default_purchase_price' => fake()->randomFloat(2, 10, 500),
            'default_sales_price' => fake()->randomFloat(2, 500, 1000),
            'reorder_point' => null,
            'is_active' => true,
            'tracking' => 'none',
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the stock of the product is tracked by lot.
     */
    public function lotTracked(): static
    {
        return $this->state(fn (array $attributes) => [
            'tracking' => 'lot',
        ]);
    }
}
