<?php

namespace App\Actions\Pos;

use App\Enums\PosOrderStatus;
use App\Enums\PosPaymentMethodType;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;

class CalculateExpectedCash
{
    /**
     * Get the cash the drawer should hold when the session closes.
     *
     * It is the opening cash plus the cash tendered on the paid orders of the session, minus the change given back.
     * Card and other payments never enter the drawer, and draft or cancelled orders have not taken any money.
     */
    public function handle(PosSession $session): float
    {
        $cashTendered = (float) DB::table('pos_payments')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_payments.pos_order_id')
            ->join('pos_payment_methods', 'pos_payment_methods.id', '=', 'pos_payments.pos_payment_method_id')
            ->where('pos_orders.pos_session_id', $session->id)
            ->where('pos_orders.status', PosOrderStatus::Paid->value)
            ->where('pos_payment_methods.type', PosPaymentMethodType::Cash->value)
            ->sum('pos_payments.amount');

        $changeGiven = (float) DB::table('pos_orders')
            ->where('pos_session_id', $session->id)
            ->where('status', PosOrderStatus::Paid->value)
            ->sum('amount_change');

        return round((float) ($session->opening_cash ?? 0) + $cashTendered - $changeGiven, 4);
    }
}
