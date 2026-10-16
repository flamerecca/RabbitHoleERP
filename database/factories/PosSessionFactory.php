<?php

namespace Database\Factories;

use App\Models\PosSession;
use App\Enums\PosSessionStatus;
use App\Models\PosConfig;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosSession>
 */
class PosSessionFactory extends Factory
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
            'pos_config_id' => fn (array $attributes) => PosConfig::factory()->create(['team_id' => $attributes['team_id']])->id,
            'session_no' => fake()->unique()->bothify('PS######/#####'),
            'status' => PosSessionStatus::Open,
            'opened_by' => fn () => User::factory()->create()->id,
            'opened_at' => now(),
            'opening_cash' => 1000,
        ];
    }

    /**
     * Indicate that the session is closed with a cash count that matches the opening cash.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PosSessionStatus::Closed,
            'closed_by' => User::factory(),
            'closed_at' => now(),
            'closing_cash_counted' => 1000,
            'closing_cash_expected' => 1000,
            'cash_difference' => 0,
        ]);
    }
}
