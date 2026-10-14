<?php

namespace App\Actions\Teams;

use App\Actions\Accounting\SeedDefaultAccounting;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    public function __construct(protected SeedDefaultAccounting $seedDefaultAccounting)
    {
    }

    /**
     * Create a new team with the default accounting data and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Team
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $team = Team::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $this->seedDefaultAccounting->handle($team);

            $membership = $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $user->switchTeam($team);

            return $team;
        });
    }
}
