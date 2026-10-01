<?php

namespace App\Actions\Products;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateInventorySettings
{
    public function __construct(protected ApplyLotTrackingRequirement $applyLotTrackingRequirement) {}

    /**
     * Update whether the team requires lot tracking, switching its products to lot tracking when the requirement turns on.
     */
    public function handle(Team $team, bool $requireLotTracking): Team
    {
        return DB::transaction(function () use ($team, $requireLotTracking) {
            $wasRequired = $team->require_lot_tracking;
            $team->update(['require_lot_tracking' => $requireLotTracking]);

            if (! $wasRequired && $requireLotTracking) {
                $this->applyLotTrackingRequirement->forTeam($team);
            }

            return $team;
        });
    }
}
