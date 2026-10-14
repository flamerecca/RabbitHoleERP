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
        return response()->json(['data' => ['require_lot_tracking' => $team->require_lot_tracking]]);
    }

    /**
     * Update the inventory settings of the team.
     */
    public function update(Request $request, Team $team, UpdateInventorySettings $updateInventorySettings): JsonResponse
    {
        $validated = $request->validate([
            'require_lot_tracking' => ['required', 'boolean'],
        ]);

        return $this->show($updateInventorySettings->handle($team, (bool) $validated['require_lot_tracking']));
    }
}
