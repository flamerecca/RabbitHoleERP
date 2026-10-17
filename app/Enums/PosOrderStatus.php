<?php

namespace App\Enums;

enum PosOrderStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
