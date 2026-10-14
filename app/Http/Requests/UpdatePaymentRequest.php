<?php

namespace App\Http\Requests;

use App\Enums\JournalType;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Get the validation rules for updating the given fields of a draft payment.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return self::paymentRules($team, 'sometimes');
    }

    /**
     * Get the rules of the payment fields that can be changed while the payment is a draft.
     *
     * @return array<string, array<mixed>>
     */
    public static function paymentRules(Team $team, string $presence): array
    {
        return [
            'journal_id' => [$presence, 'integer', Rule::exists('journals', 'id')
                ->where('team_id', $team->id)
                ->whereIn('type', [JournalType::Cash->value, JournalType::Bank->value])],
            'amount' => [$presence, 'numeric', 'gt:0'],
            'currency_id' => [$presence, 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'exchange_rate' => [$presence, 'numeric', 'gt:0'],
            'payment_date' => [$presence, 'date_format:Y-m-d'],
        ];
    }
}
