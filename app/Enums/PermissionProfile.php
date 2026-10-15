<?php

namespace App\Enums;

enum PermissionProfile: string
{
    /**
     * The role to permission mapping version two enforces.
     */
    case Current = 'current';

    /**
     * The mapping that keeps the authorization result of version one as it was before team permissions existed.
     */
    case Legacy = 'legacy';
}
