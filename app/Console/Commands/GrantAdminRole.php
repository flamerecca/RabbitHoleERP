<?php

namespace App\Console\Commands;

use App\Actions\Rbac\SyncSitePermissions;
use App\Enums\SiteRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rbac:grant-admin {email : The email of the account to promote}')]
#[Description('Give the site admin role to an existing account')]
class GrantAdminRole extends Command
{
    /**
     * Give the admin role to the account, syncing the site permissions first.
     */
    public function handle(SyncSitePermissions $sync): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No account uses this email.');

            return self::FAILURE;
        }

        $sync->handle();
        $user->assignRole(SiteRole::Admin->value);

        $this->info("{$user->email} is a site admin.");

        return self::SUCCESS;
    }
}
