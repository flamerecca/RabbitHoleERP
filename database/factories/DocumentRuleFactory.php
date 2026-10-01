<?php

namespace Database\Factories;

use App\Enums\DocumentRuleAction;
use App\Enums\DocumentRuleCondition;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Models\DocumentRule;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentRule>
 */
class DocumentRuleFactory extends Factory
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
            'document_type' => DocumentType::PurchaseOrder,
            'event' => DocumentRuleEvent::Create,
            'condition_type' => DocumentRuleCondition::TotalAmountAbove,
            'threshold' => 10000,
            'partner_ids' => null,
            'action' => DocumentRuleAction::Block,
            'message' => fake()->sentence(),
            'is_active' => true,
            'sequence' => 10,
        ];
    }

    /**
     * Indicate that the rule only warns.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => DocumentRuleAction::Warn,
        ]);
    }

    /**
     * Indicate that the rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
