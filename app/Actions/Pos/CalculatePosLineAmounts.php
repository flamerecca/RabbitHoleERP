<?php

namespace App\Actions\Pos;

class CalculatePosLineAmounts
{
    /**
     * Get the tax inclusive total and the tax of an order line, rounded to two decimals.
     *
     * The unit price includes tax, so the tax is the part of the discounted total that the tax rate accounts for.
     *
     * @return array{line_total: float, line_tax: float}
     */
    public function handle(float $quantity, float $unitPrice, float $discountRate, float $taxRate): array
    {
        $lineTotal = round($quantity * $unitPrice * (100 - $discountRate) / 100, 2);
        $lineTax = round($lineTotal - $lineTotal / (1 + $taxRate), 2);

        return ['line_total' => $lineTotal, 'line_tax' => $lineTax];
    }
}
