<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartOfAccount>
 */
class ChartOfAccountFactory extends Factory
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
            'code' => fake()->unique()->numerify('9###'),
            'name' => fake()->words(2, true),
            'account_type' => AccountType::Asset,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the account has the given type.
     */
    public function type(AccountType $accountType): static
    {
        return $this->state(fn (array $attributes) => ['account_type' => $accountType]);
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
