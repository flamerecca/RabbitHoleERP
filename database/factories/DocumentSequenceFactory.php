<?php

namespace Database\Factories;

use App\Enums\DocumentDateFormat;
use App\Enums\DocumentResetPeriod;
use App\Enums\DocumentType;
use App\Models\DocumentSequence;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSequence>
 */
class DocumentSequenceFactory extends Factory
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
            'prefix' => 'PO',
            'date_format' => DocumentDateFormat::YearMonth,
            'separator' => '/',
            'padding' => 5,
            'reset_period' => DocumentResetPeriod::Monthly,
        ];
    }
}
