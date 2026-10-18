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
     * the days before the expiry date from which stock counts as about to expire, and whether expired stock is sold at a discount of the given percentage.
     * Each setting stays as it is when none is given.
     */
    public function handle(Team $team, bool $requireLotTracking, ?int $expiryWarningDays = null, ?bool $sellExpiredAtDiscount = null, ?float $expiredDiscountRate = null): Team
    {
        return DB::transaction(function () use ($team, $requireLotTracking, $expiryWarningDays, $sellExpiredAtDiscount, $expiredDiscountRate) {
            $wasRequired = $team->require_lot_tracking;
            $team->update(array_filter([
                'require_lot_tracking' => $requireLotTracking,
                'expiry_warning_days' => $expiryWarningDays,
                'sell_expired_at_discount' => $sellExpiredAtDiscount,
                'expired_discount_rate' => $expiredDiscountRate,
            ], fn (mixed $value) => $value !== null));

            if (! $wasRequired && $requireLotTracking) {
                $this->applyLotTrackingRequirement->forTeam($team);
            }

            return $team;
        });
    }
}
