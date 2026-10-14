<?php

namespace App\Actions\Mrp;

class SplitCostLayers
{
    /**
     * Split the value of a receipt into cost layers whose values add up to it exactly, as layer unit costs hold four decimals.
     *
     * The unit cost is cut down to four decimals for all but one unit, which takes the remainder.
     *
     * @return list<array{quantity: float, unit_cost: float}>
     */
    public function handle(float $value, float $quantity): array
    {
        if ($quantity <= 1) {
            return [['quantity' => $quantity, 'unit_cost' => $value / $quantity]];
        }

        $unitCost = floor($value / $quantity * 10000) / 10000;
        $remainder = round($value - round(($quantity - 1) * $unitCost, 4), 4);

        return [
            ['quantity' => round($quantity - 1, 4), 'unit_cost' => $unitCost],
            ['quantity' => 1.0, 'unit_cost' => $remainder],
        ];
    }
}
