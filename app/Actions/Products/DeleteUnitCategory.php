<?php

namespace App\Actions\Products;

use App\Models\UnitCategory;

class DeleteUnitCategory
{
    /**
     * Delete a unit category that has no units.
     */
    public function handle(UnitCategory $unitCategory): void
    {
        abort_if($unitCategory->units()->exists(), 409, __('The unit category still has units.'));

        $unitCategory->delete();
    }
}
