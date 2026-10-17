<?php

namespace App\Enums;

enum ExpiryStatus: string
{
    case Ok = 'ok';
    case Near = 'near';
    case Expired = 'expired';
}
