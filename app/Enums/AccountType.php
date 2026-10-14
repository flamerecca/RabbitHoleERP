<?php

namespace App\Enums;

/**
 * 會計科目類型，receivable、payable 為可對帳沖銷的科目。
 */
enum AccountType: string
{
    case Asset = 'asset';
    case Receivable = 'receivable';
    case Payable = 'payable';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    /**
     * Determine if lines of the account can be reconciled with payments.
     */
    public function isReconcilable(): bool
    {
        return in_array($this, [self::Receivable, self::Payable], true);
    }
}
