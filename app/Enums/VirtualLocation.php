<?php

namespace App\Enums;

/**
 * 庫存異動另一端的虛擬位置，不對應任何資料表。
 */
enum VirtualLocation: string
{
    case Suppliers = 'Suppliers';
    case Customers = 'Customers';
    case Scrap = 'Scrap';
    case Inventory = 'Inventory';
    case Production = 'Production';
}
