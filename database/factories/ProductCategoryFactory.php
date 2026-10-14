<?php

namespace Database\Factories;

use App\Enums\CostMethod;
use App\Models\ProductCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
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
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'cost_method' => CostMethod::Average,
        ];
    }

    /**
     * Indicate that the category values its products first in, first out.
     */
    public function fifo(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_method' => CostMethod::Fifo,
        ]);
    }
}
