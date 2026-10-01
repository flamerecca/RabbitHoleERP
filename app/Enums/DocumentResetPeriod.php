<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * 單號流水號的重置週期。
 */
enum DocumentResetPeriod: string
{
    case Never = 'never';
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Daily = 'daily';

    /**
     * Get the counter key of the period the date belongs to.
     */
    public function periodKey(CarbonInterface $date): string
    {
        return match ($this) {
            self::Never => 'all',
            self::Yearly => $date->format('Y'),
            self::Monthly => $date->format('Ym'),
            self::Daily => $date->format('Ymd'),
        };
    }

    /**
     * Determine if the date segment shows enough of the date to keep numbers unique across resets.
     */
    public function isCompatibleWith(DocumentDateFormat $dateFormat): bool
    {
        return match ($this) {
            self::Never => true,
            self::Yearly => $dateFormat !== DocumentDateFormat::None,
            self::Monthly => in_array($dateFormat, [DocumentDateFormat::YearMonth, DocumentDateFormat::Date], true),
            self::Daily => $dateFormat === DocumentDateFormat::Date,
        };
    }
}
