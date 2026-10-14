<?php

namespace App\Enums;

/**
 * 存貨成本計算方法，設定在產品分類上。
 */
enum CostMethod: string
{
    case Average = 'average';
    case Fifo = 'fifo';
}
