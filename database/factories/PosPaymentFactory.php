<?php

namespace Database\Factories;

use App\Models\PosPayment;
use App\Models\PosOrder;
use App\Models\PosPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosPayment>
 */
class PosPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pos_order_id' => PosOrder::factory(),
            'pos_payment_method_id' => fn (array $attributes) => PosPaymentMethod::factory()->create(['team_id' => PosOrder::query()->whereKey($attributes['pos_order_id'])->value('team_id')])->id,
            'amount' => 105,
            'paid_at' => now(),
        ];
    }
}
