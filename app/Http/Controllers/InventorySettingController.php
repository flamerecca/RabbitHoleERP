<?php

namespace App\Http\Controllers;

use App\Actions\Products\UpdateInventorySettings;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventorySettingController extends Controller
{
    /**
     * Show the inventory settings of the team.
     */
    public function show(Team $team): JsonResponse
    {
        return response()->json(['data' => [
            'require_lot_tracking' => $team->require_lot_tracking,
            'expiry_warning_days' => $team->expiry_warning_days,
            'sell_expired_at_discount' => $team->sell_expired_at_discount,
            'expired_discount_rate' => (float) $team->expired_discount_rate,
        ]]);
    }

    /**
     * Update the inventory settings of the team.
     */
    public function update(Request $request, Team $team, UpdateInventorySettings $updateInventorySettings): JsonResponse
    {
        $validated = $request->validate([
            'require_lot_tracking' => ['required', 'boolean'],
            'expiry_warning_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'sell_expired_at_discount' => ['sometimes', 'boolean'],
            'expired_discount_rate' => ['sometimes', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ]);

        return $this->show($updateInventorySettings->handle(
            $team,
            (bool) $validated['require_lot_tracking'],
            isset($validated['expiry_warning_days']) ? (int) $validated['expiry_warning_days'] : null,
            isset($validated['sell_expired_at_discount']) ? (bool) $validated['sell_expired_at_discount'] : null,
            isset($validated['expired_discount_rate']) ? (float) $validated['expired_discount_rate'] : null,
        ));
    }
}
