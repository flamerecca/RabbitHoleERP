<?php

namespace App\Enums;

/**
 * 單據檢核規則成立時的處理方式。
 */
enum DocumentRuleAction: string
{
    case Block = 'block';
    case Warn = 'warn';
}
