<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
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
            'code' => strtoupper(fake()->unique()->lexify('X??')),
            'name' => fake()->word(),
            'symbol' => '$',
            'exchange_rate_to_base' => 1,
            'is_base' => false,
        ];
    }
}
