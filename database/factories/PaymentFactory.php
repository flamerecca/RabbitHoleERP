<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\PartnerType;
use App\Enums\PaymentType;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state, a draft receipt from a customer.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'payment_no' => fake()->unique()->numerify('RC######/#####'),
            'payment_type' => PaymentType::Receipt,
            'partner_type' => PartnerType::Customer,
            'partner_id' => Customer::factory(),
            'journal_id' => Journal::factory(),
            'amount' => 100,
            'currency_id' => Currency::factory(),
            'exchange_rate' => 1,
            'payment_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft,
            'journal_entry_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
