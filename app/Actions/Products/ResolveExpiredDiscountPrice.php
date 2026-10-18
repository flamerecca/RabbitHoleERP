<?php

namespace App\Actions\Products;

use App\Contracts\ResolvesSalesPrice;
use App\Enums\ExpiryStatus;
use App\Models\Product;
use App\Support\Expiry;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Decorates the sales price so that a product without lot tracking whose expiry date has passed is sold at the expired discount of its team.
 */
class ResolveExpiredDiscountPrice implements ResolvesSalesPrice
{
    public function __construct(protected ResolvesSalesPrice $inner)
    {
    }

    /**
     * Take the price of the decorated resolver and, when the product is expired and the team sells expired stock at a discount,
     * take the expired discount off the unit price. The list price stays and the discount rate becomes the rate of both discounts together.
     *
     * @return array{unit_price: string|null, list_price: string|null, discount_rate: string|null}
     */
    public function handle(Product $product, CarbonInterface|string $orderDate): array
    {
        $price = $this->inner->handle($product, $orderDate);
        $team = $product->team;

        if ($price['unit_price'] === null
            || ! $team->sell_expired_at_discount
            || $product->isLotTracked()
            || Expiry::statusOf($product->expiry_date, $team->expiry_warning_days, CarbonImmutable::parse($orderDate)) !== ExpiryStatus::Expired) {
            return $price;
        }

        $expiredRate = (float) $team->expired_discount_rate;
        $productRate = (float) ($price['discount_rate'] ?? 0);
        $unitPrice = round((float) $price['unit_price'] * (100 - $expiredRate) / 100, 4);
        $combinedRate = round(100 - (100 - $productRate) * (100 - $expiredRate) / 100, 4);

        return [
            'unit_price' => number_format($unitPrice, 4, '.', ''),
            'list_price' => $price['list_price'] ?? $price['unit_price'],
            'discount_rate' => number_format($combinedRate, 4, '.', ''),
        ];
    }
}
