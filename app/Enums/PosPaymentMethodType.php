<?php

namespace App\Enums;

enum PosPaymentMethodType: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Other = 'other';

    /**
     * Get the type of journal a payment method of this type must be bound to.
     */
    public function journalType(): JournalType
    {
        return match ($this) {
            self::Cash => JournalType::Cash,
            self::Card, self::Other => JournalType::Bank,
        };
    }
}
