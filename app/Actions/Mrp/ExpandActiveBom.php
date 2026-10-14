<?php

namespace App\Actions\Mrp;

use App\Models\Bom;
use App\Models\Product;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class ExpandActiveBom
{
    public function __construct(protected BomComponentFactor $factor)
    {
    }

    /**
     * Check the warehouse, product and quantity against the team and expand the active bom into component lines.
     *
     * @return array{bom: Bom, items: list<array{component_product_id: int, quantity: float}>}
     *
     * @throws ValidationException
     */
    public function handle(Team $team, int|string $productId, float|int|string $quantity, int|string|null $warehouseId = null): array
    {
        $errors = [];

        if ($warehouseId !== null && ! Warehouse::query()->where('team_id', $team->id)->whereKey($warehouseId)->exists()) {
            $errors['warehouse_id'] = __('The selected warehouse is invalid.');
        }

        if (! Product::query()->where('team_id', $team->id)->whereKey($productId)->exists()) {
            $errors['product_id'] = __('The selected product is invalid.');
        }

        if (round((float) $quantity, 4) <= 0) {
            $errors['quantity'] = __('The quantity must be greater than zero.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $bom = Bom::query()->with(['items.unit', 'items.component.unit'])
            ->where('team_id', $team->id)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->first();

        if ($bom === null) {
            throw ValidationException::withMessages(['product_id' => __('The product has no active bill of materials.')]);
        }

        $items = [];

        foreach ($bom->items as $item) {
            $componentQuantity = round((float) $quantity * $this->factor->handle($bom, $item), 4);

            if ($componentQuantity <= 0) {
                throw ValidationException::withMessages(['quantity' => __('The quantity is too small for the components.')]);
            }

            $items[] = ['component_product_id' => $item->component_product_id, 'quantity' => $componentQuantity];
        }

        return ['bom' => $bom, 'items' => $items];
    }
}
