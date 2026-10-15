<?php

use App\Actions\Rbac\SyncSitePermissions;
use App\Enums\SiteRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Sync the site permissions and give every user without a site role the operator role.
     */
    public function up(): void
    {
        app(SyncSitePermissions::class)->handle();

        User::query()->whereDoesntHave('roles')->each(function (User $user): void {
            $user->assignRole(SiteRole::Operator->value);
        });
    }

    /**
     * The roles are kept on rollback, they belong to the package tables.
     */
    public function down(): void
    {
    }
};
