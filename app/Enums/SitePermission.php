<?php

namespace App\Enums;

enum SitePermission: string
{
    case ManageRoles = 'roles.manage';
    case AssignRoles = 'users.assign_roles';

    /**
     * Get the permission names of every site resource and action.
     *
     * @return list<string>
     */
    public static function resourceNames(): array
    {
        $names = [];

        foreach (SiteResource::cases() as $resource) {
            foreach (SiteAction::cases() as $action) {
                $names[] = $resource->permission($action);
            }
        }

        return $names;
    }

    /**
     * Get the name of every site permission, resource permissions first.
     *
     * @return list<string>
     */
    public static function allNames(): array
    {
        return [...self::resourceNames(), ...array_column(self::cases(), 'value')];
    }
}
