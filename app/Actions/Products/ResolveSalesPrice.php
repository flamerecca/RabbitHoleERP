<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductDiscount;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ResolveSalesPrice
{
    /**
     * Resolve the unit price of a product on an order date, applying the active discount covering the date.
     *
     * @return array{unit_price: string|null, list_price: string|null, discount_rate: string|null}
     */
    public function handle(Product $product, CarbonInterface|string $orderDate): array
    {
        if ($product->default_sales_price === null) {
            return ['unit_price' => null, 'list_price' => null, 'discount_rate' => null];
        }

        $date = CarbonImmutable::parse($orderDate)->toDateString();
        $discount = ProductDiscount::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->first();

        $listPrice = number_format((float) $product->default_sales_price, 4, '.', '');

        if ($discount === null) {
            return ['unit_price' => $listPrice, 'list_price' => null, 'discount_rate' => null];
        }

        $unitPrice = round((float) $listPrice * (100 - (float) $discount->discount_rate) / 100, 4);

        return [
            'unit_price' => number_format($unitPrice, 4, '.', ''),
            'list_price' => $listPrice,
            'discount_rate' => number_format((float) $discount->discount_rate, 4, '.', ''),
        ];
    }
}
