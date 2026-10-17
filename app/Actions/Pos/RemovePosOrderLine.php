<?php

namespace App\Actions\Pos;

use App\Enums\TeamPermission;
use App\Models\PosOrder;
use App\Models\PosOrderLine;
use App\Models\User;

class RemovePosOrderLine
{
    use LocksDraftPosOrder;

    /**
     * Remove a line from a draft order.
     */
    public function handle(PosOrderLine $line, User $user): void
    {
        $order = $line->order;

        abort_unless($user->hasTeamPermission($order->team, TeamPermission::UpdatePosOrder), 403, __('Your team role does not allow this action.'));

        $this->changeDraftOrder($order, function (PosOrder $order) use ($line): void {
            PosOrderLine::query()->where('pos_order_id', $order->id)->whereKey($line->id)->firstOrFail()->delete();
        });
    }
}
