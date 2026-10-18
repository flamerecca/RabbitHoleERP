<?php

namespace App\Contracts;

use App\Models\Product;
use Carbon\CarbonInterface;

interface ResolvesSalesPrice
{
    /**
     * Resolve the unit price of a product on an order date.
     *
     * @return array{unit_price: string|null, list_price: string|null, discount_rate: string|null}
     */
    public function handle(Product $product, CarbonInterface|string $orderDate): array;
}
