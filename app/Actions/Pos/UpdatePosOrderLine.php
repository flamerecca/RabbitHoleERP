<?php

namespace App\Actions\Pos;

use App\Enums\TeamPermission;
use App\Models\PosOrder;
use App\Models\PosOrderLine;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdatePosOrderLine
{
    use LocksDraftPosOrder;

    public function __construct(protected CalculatePosLineAmounts $calculateLineAmounts)
    {
    }

    /**
     * Change the quantity or the manual discount of a line of a draft order.
     *
     * The price and the tax rate stay as they were snapshotted when the line was added.
     *
     * @throws ValidationException
     */
    public function handle(PosOrderLine $line, User $user, ?float $quantity = null, ?float $discountRate = null): PosOrderLine
    {
        $order = $line->order;

        abort_unless($user->hasTeamPermission($order->team, TeamPermission::UpdatePosOrder), 403, __('Your team role does not allow this action.'));

        if ($discountRate !== null && $discountRate > 0) {
            abort_unless($user->hasTeamPermission($order->team, TeamPermission::DiscountPosOrder), 403, __('Your team role does not allow this action.'));
        }

        return $this->changeDraftOrder($order, function (PosOrder $order) use ($line, $quantity, $discountRate): PosOrderLine {
            $line = PosOrderLine::query()->where('pos_order_id', $order->id)->whereKey($line->id)->firstOrFail();

            $quantity ??= (float) $line->quantity;
            $discountRate ??= (float) $line->discount_rate;
            AddPosOrderLine::ensureQuantityAndDiscount($quantity, $discountRate);

            $amounts = $this->calculateLineAmounts->handle($quantity, (float) $line->unit_price, $discountRate, (float) $line->tax_rate);

            $line->update([
                'quantity' => $quantity,
                'discount_rate' => $discountRate,
                'line_total' => $amounts['line_total'],
                'line_tax' => $amounts['line_tax'],
            ]);

            return $line;
        });
    }
}
