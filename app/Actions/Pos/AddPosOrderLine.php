<?php

namespace App\Actions\Pos;

use App\Contracts\ResolvesSalesPrice;
use App\Enums\TeamPermission;
use App\Models\PosOrder;
use App\Models\PosOrderLine;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AddPosOrderLine
{
    use LocksDraftPosOrder;

    public function __construct(
        protected ResolvesSalesPrice $resolveSalesPrice,
        protected CalculatePosLineAmounts $calculateLineAmounts,
    ) {
    }

    /**
     * Add a line to a draft order, pricing it with the product price and discount of today and the product tax rate.
     *
     * @throws ValidationException
     */
    public function handle(PosOrder $order, Product $product, float $quantity, User $user, float $discountRate = 0): PosOrderLine
    {
        abort_unless($user->hasTeamPermission($order->team, TeamPermission::UpdatePosOrder), 403, __('Your team role does not allow this action.'));

        if ($discountRate > 0) {
            abort_unless($user->hasTeamPermission($order->team, TeamPermission::DiscountPosOrder), 403, __('Your team role does not allow this action.'));
        }

        return $this->changeDraftOrder($order, function (PosOrder $order) use ($product, $quantity, $discountRate): PosOrderLine {
            $this->ensureSellable($order, $product);
            static::ensureQuantityAndDiscount($quantity, $discountRate);

            $price = $this->resolveSalesPrice->handle($product, now());
            $taxRate = $this->taxRateOf($product);
            $rate = (float) ($taxRate?->rate ?? 0);
            $amounts = $this->calculateLineAmounts->handle($quantity, (float) $price['unit_price'], $discountRate, $rate);

            return $order->lines()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'list_price' => $price['list_price'],
                'unit_price' => $price['unit_price'],
                'product_discount_rate' => $price['discount_rate'],
                'discount_rate' => $discountRate,
                'tax_rate_id' => $taxRate?->id,
                'tax_rate' => $rate,
                'line_total' => $amounts['line_total'],
                'line_tax' => $amounts['line_tax'],
            ]);
        });
    }

    /**
     * Fail validation unless the quantity is positive and the discount is a percentage.
     *
     * @throws ValidationException
     */
    public static function ensureQuantityAndDiscount(float $quantity, float $discountRate): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => __('The quantity must be greater than zero.')]);
        }

        if ($discountRate < 0 || $discountRate > 100) {
            throw ValidationException::withMessages(['discount_rate' => __('The discount must be between 0 and 100 percent.')]);
        }
    }

    /**
     * Fail validation unless the product can be sold at the POS of the order's team.
     *
     * @throws ValidationException
     */
    protected function ensureSellable(PosOrder $order, Product $product): void
    {
        $sellable = $product->team_id === $order->team_id
            && $product->is_active
            && $product->default_sales_price !== null
            && ! $product->isLotTracked();

        if (! $sellable) {
            throw ValidationException::withMessages(['product_id' => __('This product cannot be sold at the POS.')]);
        }
    }

    /**
     * Get the tax rate of the product, or the default tax rate of the team.
     */
    protected function taxRateOf(Product $product): ?TaxRate
    {
        if ($product->tax_rate_id !== null) {
            return TaxRate::query()->where('team_id', $product->team_id)->find($product->tax_rate_id);
        }

        return TaxRate::query()->where('team_id', $product->team_id)->where('is_default', true)->orderBy('id')->first();
    }
}
