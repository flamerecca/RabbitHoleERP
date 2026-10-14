<?php

namespace App\Actions\Mrp;

use App\Models\Team;

class UpdateScrapSettings
{
    /**
     * Update whether scrap orders are enabled for the team.
     */
    public function handle(Team $team, bool $scrapEnabled): Team
    {
        $team->update(['scrap_enabled' => $scrapEnabled]);

        return $team;
    }
}
