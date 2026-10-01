<?php

namespace App\Enums;

/**
 * 採購單狀態。
 */
enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
