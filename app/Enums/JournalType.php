<?php

namespace App\Enums;

/**
 * 傳票簿類型，cash、bank 為收付款使用的傳票簿。
 */
enum JournalType: string
{
    case Sales = 'sales';
    case Purchase = 'purchase';
    case Cash = 'cash';
    case Bank = 'bank';
    case General = 'general';

    /**
     * Determine if payments are posted to journals of the type, which then need a default account.
     */
    public function isForPayments(): bool
    {
        return in_array($this, [self::Cash, self::Bank], true);
    }
}
