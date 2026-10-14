<?php

namespace App\Actions\Mrp;

use App\Models\Bom;
use App\Models\BomItem;

class BomComponentFactor
{
    /**
     * Get the component quantity in its stock unit needed per stock unit of the finished product.
     *
     * The unit and the component with its unit have to be loaded on the item.
     */
    public function handle(Bom $bom, BomItem $item): float
    {
        return (float) $item->quantity * (float) $item->unit->ratio / (float) $item->component->unit->ratio / (float) $bom->quantity;
    }
}
