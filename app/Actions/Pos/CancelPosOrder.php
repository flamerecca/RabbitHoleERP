<?php

namespace App\Actions\Pos;

use App\Enums\PosOrderStatus;
use App\Enums\TeamPermission;
use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelPosOrder
{
    /**
     * Cancel a draft order. Paid orders are refunded by the refund sub project instead.
     */
    public function handle(PosOrder $order, User $user): PosOrder
    {
        abort_unless($user->hasTeamPermission($order->team, TeamPermission::CancelPosOrder), 403, __('Your team role does not allow this action.'));

        return DB::transaction(function () use ($order) {
            $order = PosOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            abort_if($order->status !== PosOrderStatus::Draft, 409, __('Only draft orders can be cancelled.'));

            $order->update(['status' => PosOrderStatus::Cancelled]);

            return $order;
        });
    }
}
