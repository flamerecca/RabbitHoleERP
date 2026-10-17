<?php

namespace App\Actions\Pos;

use App\Enums\PosOrderStatus;
use App\Enums\PosPaymentMethodType;
use App\Enums\PosSessionStatus;
use App\Enums\TeamPermission;
use App\Models\PosOrder;
use App\Models\PosPaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayPosOrder
{
    /**
     * Pay a draft order with one or more payments and give change.
     *
     * The order row is locked and its status checked again, so a second payment of the same order is refused and keeps
     * the first one. Only cash can be tendered above the amount due, so the non cash payments together may not exceed
     * it, which also keeps the change within the cash tendered.
     *
     * Deducting the stock belongs at the end of this transaction, the inventory sub project adds it there.
     *
     * @param  list<array{pos_payment_method_id: int, amount: float}>  $payments
     *
     * @throws ValidationException
     */
    public function handle(PosOrder $order, User $user, array $payments): PosOrder
    {
        abort_unless($user->hasTeamPermission($order->team, TeamPermission::PayPosOrder), 403, __('Your team role does not allow this action.'));

        return DB::transaction(function () use ($order, $payments) {
            $order = PosOrder::query()->whereKey($order->id)->lockForUpdate()->with(['posSession.posConfig', 'lines'])->firstOrFail();

            abort_if($order->status !== PosOrderStatus::Draft, 409, __('Only draft orders can be paid.'));
            abort_if($order->posSession->status !== PosSessionStatus::Open, 409, __('The session of the order is closed.'));

            if ($order->lines->isEmpty()) {
                throw ValidationException::withMessages(['payments' => __('The order has no lines.')]);
            }

            $methods = $order->posSession->posConfig->paymentMethods()->where('is_active', true)->get()->keyBy('id');
            $total = round((float) $order->amount_total, 2);
            $paid = 0.0;
            $nonCash = 0.0;
            $rows = [];

            foreach ($payments as $payment) {
                $amount = round((float) $payment['amount'], 4);
                $method = $methods->get((int) $payment['pos_payment_method_id']);

                if ($amount <= 0 || ! $method instanceof PosPaymentMethod) {
                    throw ValidationException::withMessages(['payments' => __('Each payment needs an accepted payment method and a positive amount.')]);
                }

                $paid += $amount;
                $nonCash += $method->type === PosPaymentMethodType::Cash ? 0.0 : $amount;
                $rows[] = ['pos_payment_method_id' => $method->id, 'amount' => $amount];
            }

            $paid = round($paid, 2);

            if ($paid < $total) {
                throw ValidationException::withMessages(['payments' => __('The payments do not cover the amount due.')]);
            }

            if (round($nonCash, 2) > $total) {
                throw ValidationException::withMessages(['payments' => __('Payments other than cash cannot exceed the amount due.')]);
            }

            foreach ($rows as $row) {
                $order->payments()->create([...$row, 'paid_at' => now()]);
            }

            $order->update([
                'status' => PosOrderStatus::Paid,
                'amount_paid' => $paid,
                'amount_change' => round($paid - $total, 2),
                'paid_at' => now(),
            ]);

            return $order->load('payments');
        });
    }
}
