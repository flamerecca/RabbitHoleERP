<?php

namespace Database\Factories;

use App\Models\PosConfig;
use App\Enums\JournalType;
use App\Models\Journal;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosConfig>
 */
class PosConfigFactory extends Factory
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
            'code' => fake()->unique()->bothify('POS-###'),
            'name' => fake()->city().'門市',
            'warehouse_id' => fn (array $attributes) => Warehouse::factory()->create(['team_id' => $attributes['team_id']])->id,
            'sales_journal_id' => fn (array $attributes) => Journal::factory()->create(['team_id' => $attributes['team_id'], 'type' => JournalType::Sales, 'default_account_id' => null])->id,
            'receipt_header' => null,
            'receipt_footer' => null,
            'cash_control' => true,
            'is_active' => true,
        ];
    }
}
