<?php

namespace Database\Factories;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'c1' => Team::factory(),
            'c2' => null,
            'c3' => ActivityEvent::Updated,
            'c4' => 'product',
            'c5' => 1,
            'c6' => 'product',
            'c7' => 1,
            'c8' => 'SKU-0001',
            'c9' => ['name' => ['old' => '舊名稱', 'new' => '新名稱']],
        ];
    }
}
