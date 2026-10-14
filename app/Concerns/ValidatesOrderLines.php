<?php

namespace App\Concerns;

use App\Models\Product;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesOrderLines
{
    /**
     * Get the validation rules of order lines under the given key prefix, such as `items.*.`.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function orderLineRules(Team $team, string $prefix): array
    {
        return [
            "{$prefix}product_id" => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            "{$prefix}unit_id" => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            "{$prefix}quantity" => ['required', 'numeric', 'gt:0'],
            "{$prefix}unit_price" => ['required', 'numeric', 'min:0'],
            "{$prefix}tax_rate_id" => ['nullable', 'integer', Rule::exists('tax_rates', 'id')->where('team_id', $team->id)],
        ];
    }

    /**
     * Add an error for every line whose unit belongs to another category than the product stock unit.
     * An empty key prefix reports the error on `unit_id` for a single line request.
     *
     * @param  array<int|string, mixed>  $lines
     */
    protected function validateOrderLineUnits(Validator $validator, array $lines, string $keyPrefix): void
    {
        foreach ($lines as $index => $line) {
            if (! is_array($line) || empty($line['unit_id']) || empty($line['product_id'])) {
                continue;
            }

            $product = Product::query()->with('unit')->whereKey($line['product_id'])->first();
            $unit = Unit::query()->whereKey($line['unit_id'])->first();

            if ($product !== null && $unit !== null && $unit->category_id !== $product->unit->category_id) {
                $validator->errors()->add($keyPrefix === '' ? 'unit_id' : "{$keyPrefix}{$index}.unit_id", __('The unit must belong to the same unit category as the product stock unit.'));
            }
        }
    }
}
