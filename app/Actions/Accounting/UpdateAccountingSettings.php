<?php

namespace App\Actions\Accounting;

use App\Models\AccountingSetting;
use App\Models\Team;

class UpdateAccountingSettings
{
    /**
     * Replace the accounts and journals the automatic posting of the team uses.
     *
     * @param  array<string, int>  $attributes
     */
    public function handle(Team $team, array $attributes): AccountingSetting
    {
        return AccountingSetting::query()->updateOrCreate(['team_id' => $team->id], $attributes);
    }
}
