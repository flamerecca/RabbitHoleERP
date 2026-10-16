<?php

namespace App\Actions\Pos;

use App\Enums\DocumentType;
use App\Enums\PosSessionStatus;
use App\Enums\TeamPermission;
use App\Models\PosConfig;
use App\Models\PosSession;
use App\Models\User;
use App\Services\DocumentSequence;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenPosSession
{
    public function __construct(protected DocumentSequence $documentSequence)
    {
    }

    /**
     * Open a session of the POS store, with the opening cash when the store counts cash.
     *
     * The store row is locked so two cashiers opening at once are served one after the other, and the partial unique
     * index on open sessions stays the last line of defence.
     *
     * @throws ValidationException
     */
    public function handle(PosConfig $config, User $user, ?float $openingCash = null): PosSession
    {
        abort_unless($user->hasTeamPermission($config->team, TeamPermission::OpenPosSession), 403, __('Your team role does not allow this action.'));

        return DB::transaction(function () use ($config, $user, $openingCash) {
            $config = PosConfig::query()->whereKey($config->id)->lockForUpdate()->firstOrFail();

            if (! $config->is_active) {
                throw ValidationException::withMessages(['pos_config_id' => __('The POS store is inactive.')]);
            }

            if (! $config->paymentMethods()->exists()) {
                throw ValidationException::withMessages(['pos_config_id' => __('The POS store has no payment methods.')]);
            }

            if ($this->hasOpenSession($config)) {
                throw $this->alreadyOpen();
            }

            if ($config->cash_control && ($openingCash === null || $openingCash < 0)) {
                throw ValidationException::withMessages(['opening_cash' => __('The opening cash must be zero or more.')]);
            }

            try {
                return PosSession::create([
                    'team_id' => $config->team_id,
                    'pos_config_id' => $config->id,
                    'session_no' => $this->documentSequence->next($config->team, DocumentType::PosSession, now()),
                    'status' => PosSessionStatus::Open,
                    'opened_by' => $user->id,
                    'opened_at' => now(),
                    'opening_cash' => $config->cash_control ? round($openingCash, 4) : null,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw $this->alreadyOpen();
            }
        });
    }

    /**
     * Determine if the config has an open session.
     */
    protected function hasOpenSession(PosConfig $config): bool
    {
        return PosSession::query()
            ->where('pos_config_id', $config->id)
            ->where('status', PosSessionStatus::Open->value)
            ->exists();
    }

    /**
     * Build the error for a config that already has an open session.
     */
    protected function alreadyOpen(): ValidationException
    {
        return ValidationException::withMessages(['pos_config_id' => __('The POS store already has an open session.')]);
    }
}
