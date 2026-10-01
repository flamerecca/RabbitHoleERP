<?php

namespace App\Enums;

/**
 * 銷售訂單狀態。
 */
enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyShipped = 'partially_shipped';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';
}
