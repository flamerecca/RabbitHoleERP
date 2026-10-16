<?php

namespace Database\Factories;

use App\Models\PosPaymentMethod;
use App\Enums\AccountType;
use App\Enums\JournalType;
use App\Enums\PosPaymentMethodType;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosPaymentMethod>
 */
class PosPaymentMethodFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'type' => PosPaymentMethodType::Cash,
            'journal_id' => fn (array $attributes) => Journal::factory()->create([
                'team_id' => $attributes['team_id'],
                'type' => JournalType::Cash,
                'default_account_id' => ChartOfAccount::factory()->create(['team_id' => $attributes['team_id'], 'account_type' => AccountType::Asset])->id,
            ])->id,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the method is a card payment bound to a bank journal.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PosPaymentMethodType::Card,
            'journal_id' => Journal::factory()->create([
                'team_id' => $attributes['team_id'],
                'type' => JournalType::Bank,
                'default_account_id' => ChartOfAccount::factory()->create(['team_id' => $attributes['team_id'], 'account_type' => AccountType::Asset])->id,
            ])->id,
        ]);
    }
}
