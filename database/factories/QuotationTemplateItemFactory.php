<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationTemplateItem>
 */
class QuotationTemplateItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quotation_template_id' => QuotationTemplate::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->create([
                'team_id' => QuotationTemplate::query()->findOrFail($attributes['quotation_template_id'])->team_id,
            ])->id,
            'quantity' => 1,
        ];
    }
}
