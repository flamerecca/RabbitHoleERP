<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * 單號中的日期區段格式。
 */
enum DocumentDateFormat: string
{
    case None = 'none';
    case Year = 'year';
    case YearMonth = 'year_month';
    case Date = 'date';

    /**
     * Format the date segment of a document number.
     */
    public function format(CarbonInterface $date): string
    {
        return match ($this) {
            self::None => '',
            self::Year => $date->format('Y'),
            self::YearMonth => $date->format('Ym'),
            self::Date => $date->format('Ymd'),
        };
    }
}
