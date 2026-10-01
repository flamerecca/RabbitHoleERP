<?php

namespace App\Enums;

/**
 * 單據檢核規則的條件類型。
 */
enum DocumentRuleCondition: string
{
    case TotalAmountAbove = 'total_amount_above';
    case LineQuantityAbove = 'line_quantity_above';
    case PartnerInList = 'partner_in_list';
    case CreditLimitExceeded = 'credit_limit_exceeded';

    /**
     * Determine if the condition needs a threshold.
     */
    public function needsThreshold(): bool
    {
        return in_array($this, [self::TotalAmountAbove, self::LineQuantityAbove], true);
    }

    /**
     * Determine if the condition can be used for the document type.
     */
    public function supports(DocumentType $documentType): bool
    {
        return match ($this) {
            self::TotalAmountAbove => in_array($documentType, [DocumentType::PurchaseOrder, DocumentType::SalesOrder], true),
            self::CreditLimitExceeded => $documentType === DocumentType::SalesOrder,
            default => $documentType->supportsRules(),
        };
    }
}
