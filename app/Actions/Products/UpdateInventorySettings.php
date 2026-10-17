<?php

namespace App\Actions\Products;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateInventorySettings
{
    public function __construct(protected ApplyLotTrackingRequirement $applyLotTrackingRequirement)
    {
    }

    /**
     * Update whether the team requires lot tracking, switching its products to lot tracking when the requirement turns on,
     * and the days before the expiry date from which stock counts as about to expire. The warning days stay as they are when none is given.
     */
    public function handle(Team $team, bool $requireLotTracking, ?int $expiryWarningDays = null): Team
    {
        return DB::transaction(function () use ($team, $requireLotTracking, $expiryWarningDays) {
            $wasRequired = $team->require_lot_tracking;
            $team->update(array_filter([
                'require_lot_tracking' => $requireLotTracking,
                'expiry_warning_days' => $expiryWarningDays,
            ], fn (mixed $value) => $value !== null));

            if (! $wasRequired && $requireLotTracking) {
                $this->applyLotTrackingRequirement->forTeam($team);
            }

            return $team;
        });
    }
}
