<?php

namespace App\Http\Middleware;

use App\Enums\PermissionProfile;
use App\Enums\TeamPermission;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamPermission
{
    /**
     * Ensure the team role of the user has every permission the route declares.
     *
     * The first parameter is the permission profile, the rest are permission values, for example
     * `EnsureTeamPermission::class.':current,purchase_order:confirm'`.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $profile, string ...$permissions): Response
    {
        $user = $request->user();
        $team = $this->team($request);

        abort_if(! $user || ! $team || ! $user->belongsToTeam($team), 403, __('You are not a member of this team.'));

        $role = $user->teamRole($team);
        $profile = PermissionProfile::from($profile);

        foreach ($permissions as $value) {
            abort_unless(
                $role?->hasPermission(TeamPermission::from($value), $profile),
                403,
                __('Your team role does not allow this action.'),
            );
        }

        return $next($request);
    }

    /**
     * Get the team associated with the request.
     */
    protected function team(Request $request): ?Team
    {
        $team = $request->route('current_team') ?? $request->route('team');

        if (is_string($team)) {
            $team = Team::where('slug', $team)->first();
        }

        return $team;
    }
}
