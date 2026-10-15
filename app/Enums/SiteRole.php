<?php

namespace App\Enums;

enum SiteRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
}
