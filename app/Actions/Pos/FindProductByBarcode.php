<?php

namespace App\Actions\Pos;

use App\Models\Product;
use App\Models\Team;

class FindProductByBarcode
{
    /**
     * Find the active product of the team with the scanned barcode.
     */
    public function handle(Team $team, string $barcode): ?Product
    {
        return Product::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->where('barcode', trim($barcode))
            ->first();
    }
}
