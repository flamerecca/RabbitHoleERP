<?php

namespace App\Actions\Pos;

use App\Enums\PosOrderStatus;
use App\Enums\PosSessionStatus;
use App\Models\PosOrder;
use Closure;
use Illuminate\Support\Facades\DB;

trait LocksDraftPosOrder
{
    /**
     * Lock the order, make sure it is still a draft of an open session, run the change and recalculate the totals.
     *
     * @template TResult
     *
     * @param  Closure(PosOrder): TResult  $change
     * @return TResult
     */
    protected function changeDraftOrder(PosOrder $order, Closure $change): mixed
    {
        return DB::transaction(function () use ($order, $change) {
            $order = PosOrder::query()->whereKey($order->id)->lockForUpdate()->with('posSession')->firstOrFail();

            abort_if($order->status !== PosOrderStatus::Draft, 409, __('Only draft orders can be changed.'));
            abort_if($order->posSession->status !== PosSessionStatus::Open, 409, __('The session of the order is closed.'));

            $result = $change($order);

            app(CalculatePosOrderTotals::class)->handle($order);

            return $result;
        });
    }
}
