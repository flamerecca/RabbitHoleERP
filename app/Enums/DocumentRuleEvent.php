<?php

namespace App\Enums;

/**
 * 單據檢核規則的檢核時機。
 */
enum DocumentRuleEvent: string
{
    case Create = 'create';
    case Update = 'update';
    case Confirm = 'confirm';
}
