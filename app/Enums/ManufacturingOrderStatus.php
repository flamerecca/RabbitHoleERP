<?php

namespace App\Enums;

/**
 * 生產單的狀態，需要完成這一步，所以不沿用 DocumentStatus。
 */
enum ManufacturingOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Cancelled = 'cancelled';
}
