<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentStatus;
use App\Models\ScrapOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateScrapOrder
{
    public function __construct(protected ValidateScrapOrderInput $validateInput)
    {
    }

    /**
     * Update the reason and optionally replace the lines of a draft scrap order; the warehouse never changes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ScrapOrder $order, array $attributes): ScrapOrder
    {
        return DB::transaction(function () use ($order, $attributes) {
            $order = ScrapOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== DocumentStatus::Draft, 409, __('Only draft scrap orders can be updated.'));

            $this->validateInput->handle($order->team, Arr::only($attributes, ['items']));

            $order->update(Arr::only($attributes, ['reason']));

            if (array_key_exists('items', $attributes)) {
                $order->items()->get()->each->delete();
                $order->items()->createMany($attributes['items']);
            }

            return $order->fresh('items');
        });
    }
}
