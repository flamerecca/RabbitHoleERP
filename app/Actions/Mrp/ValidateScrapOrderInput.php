<?php

namespace App\Actions\Mrp;

use App\Models\Product;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class ValidateScrapOrderInput
{
    /**
     * Check the warehouse and lines of a scrap order against the team.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes): void
    {
        $errors = [];

        if (array_key_exists('warehouse_id', $attributes)
            && ! Warehouse::query()->where('team_id', $team->id)->whereKey($attributes['warehouse_id'])->exists()) {
            $errors['warehouse_id'] = __('The selected warehouse is invalid.');
        }

        if (array_key_exists('items', $attributes)) {
            $lines = array_values((array) $attributes['items']);
            $productIds = array_column($lines, 'product_id');

            if ($lines === []) {
                $errors['items'] = __('A scrap order needs at least one line.');
            } elseif (count($productIds) !== count(array_unique($productIds))) {
                $errors['items'] = __('A product can appear only once on a scrap order.');
            } elseif (Product::query()->where('team_id', $team->id)->whereKey($productIds)->count() !== count($productIds)) {
                $errors['items'] = __('The selected product is invalid.');
            } elseif (array_filter(array_column($lines, 'quantity'), fn (mixed $quantity) => (float) $quantity <= 0) !== []) {
                $errors['items'] = __('The quantity must be greater than zero.');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
