<?php

namespace App\Support\Rbac;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;

class TeamAuthorizer
{
    /**
     * Determine if the signed in user has the team permission in the current admin panel tenant.
     */
    public static function allows(TeamPermission $permission): bool
    {
        $team = Filament::getTenant();
        $user = auth()->user();

        return $team instanceof Team && $user instanceof User && $user->hasTeamPermission($team, $permission);
    }
}
