<?php

namespace App\Actions\Hr;

use App\Models\Team;

class UpdateHrSettings
{
    /**
     * Update whether the HR module is enabled for the team.
     */
    public function handle(Team $team, bool $hrEnabled): Team
    {
        $team->update(['hr_enabled' => $hrEnabled]);

        return $team;
    }
}
