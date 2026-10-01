<?php

namespace App\Actions\Sales;

use App\Enums\ConsignmentHoldStatus;
use App\Models\ConsignmentHold;
use Illuminate\Support\Facades\DB;

class MarkConsignmentHoldPickedUp
{
    /**
     * Record that the customer picked up the held goods.
     */
    public function handle(ConsignmentHold $hold): ConsignmentHold
    {
        return DB::transaction(function () use ($hold) {
            $hold = ConsignmentHold::query()->lockForUpdate()->findOrFail($hold->id);

            abort_unless(
                in_array($hold->status, [ConsignmentHoldStatus::Holding, ConsignmentHoldStatus::Overdue], true),
                409,
                __('Only held or overdue consignment holds can be picked up.'),
            );

            $hold->update(['status' => ConsignmentHoldStatus::PickedUp, 'picked_up_at' => now()]);

            return $hold;
        });
    }
}
