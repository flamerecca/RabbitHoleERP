<?php

namespace App\Actions\Inventory;

use App\Enums\TeamPermission;
use App\Models\StockLot;
use App\Models\User;
use Carbon\CarbonInterface;

class SetLotExpiryDate
{
    /**
     * Set or remove the expiry date of a lot.
     *
     * A date in the past is accepted on purpose, because the dates of old lots are filled in later and the lot then
     * shows as expired.
     */
    public function handle(StockLot $lot, ?CarbonInterface $expiryDate, User $user): StockLot
    {
        abort_unless($user->hasTeamPermission($lot->team, TeamPermission::UpdateStockLot), 403, __('Your team role does not allow this action.'));

        $lot->update(['expiry_date' => $expiryDate?->toDateString()]);

        return $lot;
    }
}
