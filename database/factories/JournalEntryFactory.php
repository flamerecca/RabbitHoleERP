<?php

namespace Database\Factories;

use App\Enums\JournalEntryStatus;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
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
            'journal_id' => Journal::factory(),
            'entry_no' => fake()->unique()->numerify('JE######/#####'),
            'entry_date' => now()->toDateString(),
            'reference_type' => null,
            'reference_id' => null,
            'status' => JournalEntryStatus::Posted,
            'description' => null,
            'created_by' => User::factory(),
        ];
    }
}
