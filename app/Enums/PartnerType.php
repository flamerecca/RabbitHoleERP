<?php

namespace App\Enums;

use App\Models\Customer;
use App\Models\Supplier;

/**
 * 應收應付明細與收付款單的交易夥伴類型。
 */
enum PartnerType: string
{
    case Supplier = 'supplier';
    case Customer = 'customer';

    /**
     * Get the model class of the partner.
     *
     * @return class-string<Supplier|Customer>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Supplier => Supplier::class,
            self::Customer => Customer::class,
        };
    }
}
