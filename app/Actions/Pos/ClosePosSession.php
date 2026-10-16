<?php

namespace App\Actions\Pos;

use App\Enums\PosSessionStatus;
use App\Enums\TeamPermission;
use App\Models\PosSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClosePosSession
{
    public function __construct(protected CalculateExpectedCash $calculateExpectedCash)
    {
    }

    /**
     * Close the session, counting the cash when the POS store asks for it.
     *
     * The session row is locked and its status checked again, so a second close of the same session is refused and
     * keeps the result of the first. The opener may close with the close permission, anybody with the close any
     * permission may close any session of the team.
     *
     * @throws ValidationException
     */
    public function handle(PosSession $session, User $user, ?float $countedCash = null, ?string $note = null): PosSession
    {
        return DB::transaction(function () use ($session, $user, $countedCash, $note) {
            $session = PosSession::query()->whereKey($session->id)->lockForUpdate()->with(['posConfig', 'team'])->firstOrFail();

            abort_if($session->status !== PosSessionStatus::Open, 409, __('Only open sessions can be closed.'));

            abort_unless($this->mayClose($session, $user), 403, __('Your team role does not allow this action.'));

            $attributes = [
                'status' => PosSessionStatus::Closed,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closing_note' => $note,
            ];

            if ($session->posConfig->cash_control) {
                if ($countedCash === null || $countedCash < 0) {
                    throw ValidationException::withMessages(['closing_cash_counted' => __('The counted cash must be zero or more.')]);
                }

                $counted = round($countedCash, 4);
                $expected = round($this->calculateExpectedCash->handle($session), 4);

                $attributes += [
                    'closing_cash_counted' => $counted,
                    'closing_cash_expected' => $expected,
                    'cash_difference' => round($counted - $expected, 4),
                ];
            }

            $session->update($attributes);

            return $session;
        });
    }

    /**
     * Determine if the user may close the session.
     */
    protected function mayClose(PosSession $session, User $user): bool
    {
        if ($user->hasTeamPermission($session->team, TeamPermission::CloseAnyPosSession)) {
            return true;
        }

        return $session->opened_by === $user->id && $user->hasTeamPermission($session->team, TeamPermission::ClosePosSession);
    }
}
