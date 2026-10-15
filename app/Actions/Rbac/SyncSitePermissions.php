<?php

namespace App\Actions\Rbac;

use App\Enums\SitePermission;
use App\Enums\SiteRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncSitePermissions
{
    /**
     * Create the missing site permissions and default roles.
     *
     * The admin role always gets every permission. The operator role only gets the resource permissions when it is
     * created, so permissions an administrator changed later are kept.
     */
    public function handle(): void
    {
        foreach (SitePermission::allNames() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate(SiteRole::Admin->value, 'web');
        $admin->syncPermissions(SitePermission::allNames());

        $operator = Role::findOrCreate(SiteRole::Operator->value, 'web');

        if ($operator->wasRecentlyCreated) {
            $operator->syncPermissions(SitePermission::resourceNames());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
