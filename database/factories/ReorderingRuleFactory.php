<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ReorderingRule;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReorderingRule>
 */
class ReorderingRuleFactory extends Factory
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
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'min_quantity' => 10,
            'max_quantity' => 100,
            'multiple_quantity' => 1,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the reordering rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
