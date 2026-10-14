<?php

namespace App\Enums;

/**
 * 收付款單類型，receipt 為向客戶收款、payment 為付款給供應商。
 */
enum PaymentType: string
{
    case Receipt = 'receipt';
    case Payment = 'payment';

    /**
     * Get the partner type the payment type is made with.
     */
    public function partnerType(): PartnerType
    {
        return match ($this) {
            self::Receipt => PartnerType::Customer,
            self::Payment => PartnerType::Supplier,
        };
    }

    /**
     * Get the document type numbering payments of the type.
     */
    public function documentType(): DocumentType
    {
        return match ($this) {
            self::Receipt => DocumentType::PaymentReceipt,
            self::Payment => DocumentType::PaymentPayment,
        };
    }
}
