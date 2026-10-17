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
        $totals = PosOrderLine::query()
            ->where('pos_order_id', $order->id)
            ->selectRaw('coalesce(sum(line_total), 0) as total, coalesce(sum(line_tax), 0) as tax')
            ->first();

        $order->update([
            'amount_total' => round((float) $totals->total, 4),
            'amount_tax' => round((float) $totals->tax, 4),
        ]);
    }
}
