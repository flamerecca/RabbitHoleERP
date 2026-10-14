<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentStatus;
use App\Models\UnbuildOrder;
use Illuminate\Support\Facades\DB;

class UpdateUnbuildOrder
{
    public function __construct(protected PrepareUnbuildOrder $prepare)
    {
    }

    /**
     * Update the reason and quantity of a draft unbuild order and expand its components again; the product and warehouse never change.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(UnbuildOrder $order, array $attributes): UnbuildOrder
    {
        return DB::transaction(function () use ($order, $attributes) {
            $order = UnbuildOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== DocumentStatus::Draft, 409, __('Only draft unbuild orders can be updated.'));

            if (array_key_exists('reason', $attributes)) {
                $order->update(['reason' => $attributes['reason']]);
            }

            if (array_key_exists('quantity', $attributes)) {
                $prepared = $this->prepare->handle($order->team, $order->product_id, $attributes['quantity']);

                $order->update(['quantity' => $attributes['quantity'], 'bom_id' => $prepared['bom']->id]);
                $order->items()->get()->each->delete();
                $order->items()->createMany($prepared['items']);
            }

            return $order->fresh('items');
        });
    }
}
