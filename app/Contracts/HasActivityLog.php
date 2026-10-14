<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * A model whose changes are written to the activity log.
 */
interface HasActivityLog
{
    /**
     * Get the product or document header the activity belongs to.
     */
    public function activityDocument(): Model&HasActivityLog;

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array;

    /**
     * Get the document number or SKU that identifies the record in the activity log.
     */
    public function activityLabel(): ?string;
}
