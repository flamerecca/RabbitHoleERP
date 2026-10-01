<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
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
            'name' => fake()->company(),
            'code' => fake()->unique()->bothify('CUS-###'),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'tax_id' => fake()->numerify('########'),
            'currency_id' => null,
            'credit_limit' => null,
            'payment_terms' => '月結 30 天',
        ];
    }
}
