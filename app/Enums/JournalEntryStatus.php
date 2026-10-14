<?php

namespace App\Enums;

/**
 * 分錄狀態，自動過帳的分錄直接為 posted。
 */
enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
