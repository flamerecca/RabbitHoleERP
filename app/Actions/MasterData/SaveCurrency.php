<?php

namespace App\Actions\MasterData;

use App\Models\Currency;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveCurrency
{
    /**
     * Create or update a currency, keeping exactly one base currency per team with an exchange rate of 1.
     *
     * Only the first base currency of a team can be marked as base, and the base flag can never be removed.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes, ?Currency $currency = null): Currency
    {
        return DB::transaction(function () use ($team, $attributes, $currency) {
            $wasBase = (bool) $currency?->is_base;
            $currency ??= new Currency(['team_id' => $team->id, 'is_base' => false]);
            $currency->fill($attributes);

            if ($wasBase && ! $currency->is_base) {
                throw ValidationException::withMessages(['is_base' => __('The base currency cannot be changed.')]);
            }

            if ($currency->is_base && ! $wasBase
                && Currency::query()->where('team_id', $team->id)->where('is_base', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['is_base' => __('The team already has a base currency.')]);
            }

            if ($currency->is_base && (float) $currency->exchange_rate_to_base !== 1.0) {
                throw ValidationException::withMessages(['exchange_rate_to_base' => __('The exchange rate of the base currency must be 1.')]);
            }

            $currency->save();

            return $currency;
        });
    }
}
