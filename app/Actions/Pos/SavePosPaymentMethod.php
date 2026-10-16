<?php

namespace App\Actions\Pos;

use App\Enums\PosPaymentMethodType;
use App\Models\Journal;
use App\Models\PosPaymentMethod;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePosPaymentMethod
{
    /**
     * Create or update a payment method of the team.
     *
     * The journal must belong to the team and match the method type, a cash journal for cash and a bank journal for
     * card and other.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes, ?PosPaymentMethod $method = null): PosPaymentMethod
    {
        return DB::transaction(function () use ($team, $attributes, $method) {
            $type = PosPaymentMethodType::from((string) $attributes['type']);

            $journalMatches = Journal::query()
                ->where('team_id', $team->id)
                ->whereKey($attributes['journal_id'])
                ->where('type', $type->journalType()->value)
                ->exists();

            if (! $journalMatches) {
                throw ValidationException::withMessages(['journal_id' => __('The journal must be a :type journal of the team.', ['type' => $type->journalType()->value])]);
            }

            $nameTaken = PosPaymentMethod::query()
                ->where('team_id', $team->id)
                ->where('name', $attributes['name'])
                ->when($method !== null, fn ($query) => $query->whereKeyNot($method->id))
                ->exists();

            if ($nameTaken) {
                throw ValidationException::withMessages(['name' => __('The payment method name is already used.')]);
            }

            $method ??= new PosPaymentMethod(['team_id' => $team->id]);
            $method->fill($attributes)->save();

            return $method;
        });
    }
}
