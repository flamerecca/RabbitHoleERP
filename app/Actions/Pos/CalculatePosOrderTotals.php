<?php

namespace App\Actions\Pos;

use App\Models\PosOrder;
use App\Models\PosOrderLine;

class CalculatePosOrderTotals
{
    /**
     * Recalculate the total and the tax of the order from its lines.
     *
     * The tax of each line is already rounded, the order only adds them up, so the amounts on the receipt always add up.
     */
    public function handle(PosOrder $order): void
    {
        $lines = PosOrderLine::query()->where('pos_order_id', $order->id);

        $order->update([
            'amount_total' => round((float) $lines->sum('line_total'), 4),
            'amount_tax' => round((float) $lines->sum('line_tax'), 4),
        ]);
    }
}
