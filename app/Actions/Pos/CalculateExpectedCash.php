<?php

namespace App\Actions\Pos;

use App\Models\PosSession;

class CalculateExpectedCash
{
    /**
     * Get the cash the drawer should hold when the session closes.
     *
     * There are no POS payments yet, so it is the opening cash. The payments table of the checkout sub project adds
     * the cash payments of the session here, closing the session does not change.
     */
    public function handle(PosSession $session): float
    {
        return (float) ($session->opening_cash ?? 0);
    }
}
