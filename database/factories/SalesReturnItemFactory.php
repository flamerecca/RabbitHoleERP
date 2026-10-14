<?php

namespace Database\Factories;

use App\Enums\SalesReturnDisposition;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesReturnItem>
 */
class SalesReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_return_id' => SalesReturn::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'disposition' => SalesReturnDisposition::Restock,
        ];
    }

    /**
     * Indicate that the returned goods are scrapped.
     */
    public function scrapped(): static
    {
        return $this->state(fn (array $attributes) => [
            'disposition' => SalesReturnDisposition::Scrap,
        ]);
    }
}
