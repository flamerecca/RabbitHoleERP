<?php

namespace App\Actions\Pos;

use App\Enums\DocumentType;
use App\Enums\PosOrderStatus;
use App\Enums\PosSessionStatus;
use App\Enums\TeamPermission;
use App\Models\Customer;
use App\Models\PosOrder;
use App\Models\PosSession;
use App\Models\User;
use App\Services\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePosOrder
{
    public function __construct(protected DocumentSequence $documentSequence)
    {
    }

    /**
     * Create a draft order in an open session.
     *
     * The session row is locked so an order cannot slip into a session that is being closed.
     *
     * @throws ValidationException
     */
    public function handle(PosSession $session, User $user, ?Customer $customer = null): PosOrder
    {
        abort_unless($user->hasTeamPermission($session->team, TeamPermission::CreatePosOrder), 403, __('Your team role does not allow this action.'));

        return DB::transaction(function () use ($session, $user, $customer) {
            $session = PosSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->status !== PosSessionStatus::Open) {
                throw ValidationException::withMessages(['pos_session_id' => __('Orders can only be created in an open session.')]);
            }

            if ($customer !== null && $customer->team_id !== $session->team_id) {
                throw ValidationException::withMessages(['customer_id' => __('The customer must belong to the team.')]);
            }

            return PosOrder::create([
                'team_id' => $session->team_id,
                'pos_session_id' => $session->id,
                'order_no' => $this->documentSequence->next($session->team, DocumentType::PosOrder, now()),
                'status' => PosOrderStatus::Draft,
                'customer_id' => $customer?->id,
                'created_by' => $user->id,
            ]);
        });
    }
}
