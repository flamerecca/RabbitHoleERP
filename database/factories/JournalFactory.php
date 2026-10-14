<?php

namespace Database\Factories;

use App\Enums\JournalType;
use App\Models\Journal;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
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
            'code' => strtoupper(fake()->unique()->lexify('J???')),
            'name' => fake()->words(2, true),
            'type' => JournalType::General,
            'default_account_id' => null,
        ];
    }
}
