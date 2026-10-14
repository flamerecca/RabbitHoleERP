<?php

namespace App\Actions\Mrp;

use App\Models\Team;

class UpdateMrpSettings
{
    /**
     * Update whether the MRP module is enabled for the team.
     */
    public function handle(Team $team, bool $mrpEnabled): Team
    {
        $team->update(['mrp_enabled' => $mrpEnabled]);

        return $team;
    }
}
