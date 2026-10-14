<?php

namespace App\Actions\Products;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitUsage
{
    /**
     * Determine if a product or an order line refers to the unit.
     */
    public function isUsed(Unit $unit): bool
    {
        return DB::table('products')->where('unit_id', $unit->id)->orWhere('purchase_unit_id', $unit->id)->exists()
            || DB::table('purchase_order_items')->where('unit_id', $unit->id)->exists()
            || DB::table('sales_order_items')->where('unit_id', $unit->id)->exists();
    }
}
