<?php

namespace App\Enums;

/**
 * 操作紀錄的動作。
 */
enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
