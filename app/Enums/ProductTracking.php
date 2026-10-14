<?php

namespace App\Enums;

/**
 * 產品的庫存追蹤方式。
 */
enum ProductTracking: string
{
    case None = 'none';
    case Lot = 'lot';
}
