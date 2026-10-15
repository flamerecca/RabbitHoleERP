<?php

namespace App\Actions\Rbac;

use App\Enums\SiteRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AssignSiteRoles
{
    /**
     * Replace the site roles of the user, keeping at least one admin.
     *
     * @param  list<string>  $roleNames
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $roleNames): void
    {
        $known = Role::query()->where('guard_name', 'web')->whereIn('name', $roleNames)->pluck('name')->all();

        if (count($known) !== count(array_unique($roleNames))) {
            throw ValidationException::withMessages(['roles' => __('Some roles do not exist.')]);
        }

        DB::transaction(function () use ($user, $roleNames): void {
            $wasAdmin = $user->hasRole(SiteRole::Admin->value);
            $staysAdmin = in_array(SiteRole::Admin->value, $roleNames, true);

            if ($wasAdmin && ! $staysAdmin && User::role(SiteRole::Admin->value)->lockForUpdate()->pluck('users.id')->count() <= 1) {
                throw ValidationException::withMessages(['roles' => __('At least one admin is required.')]);
            }

            $user->syncRoles($roleNames);
        });
    }
}
