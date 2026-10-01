<?php

namespace App\Enums;

/**
 * 銷售退貨明細的去向。
 */
enum SalesReturnDisposition: string
{
    case Restock = 'restock';
    case Scrap = 'scrap';
}
