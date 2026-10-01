<?php

namespace Database\Factories;

use App\Enums\ConsignmentHoldStatus;
use App\Models\ConsignmentHold;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsignmentHold>
 */
class ConsignmentHoldFactory extends Factory
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
            'shipment_id' => Shipment::factory(),
            'warehouse_id' => Warehouse::factory(),
            'hold_no' => fake()->unique()->numerify('CH######/#####'),
            'held_from' => now()->toDateString(),
            'held_until' => now()->addDays(15)->toDateString(),
            'status' => ConsignmentHoldStatus::Holding,
            'picked_up_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
