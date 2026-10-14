<?php

namespace Database\Factories;

use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
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
            'name' => '營業稅',
            'rate' => 0.05,
            'is_default' => false,
        ];
    }
}
