<?php

namespace App\Enums;

/**
 * 產品分類的批號追蹤要求，null 代表依 Team 設定。
 */
enum LotTrackingPolicy: string
{
    case Required = 'required';
    case Optional = 'optional';
}
