<?php

namespace Database\Factories;

use App\Models\PosOrder;
use App\Enums\PosOrderStatus;
use App\Models\PosSession;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosOrder>
 */
class PosOrderFactory extends Factory
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
            'pos_session_id' => fn (array $attributes) => PosSession::factory()->create(['team_id' => $attributes['team_id']])->id,
            'order_no' => fake()->unique()->bothify('POS######/#####'),
            'status' => PosOrderStatus::Draft,
            'created_by' => fn () => User::factory()->create()->id,
        ];
    }

    /**
     * Indicate that the order is paid in full with cash.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PosOrderStatus::Paid,
            'amount_total' => 105,
            'amount_tax' => 5,
            'amount_paid' => 105,
            'paid_at' => now(),
        ]);
    }
}
