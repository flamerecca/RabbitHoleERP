<?php

namespace App\Actions\Sales;

use App\Enums\ConsignmentHoldStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\ConsignmentHold;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateConsignmentHold
{
    public function __construct(protected DocumentSequence $documentSequence) {}

    /**
     * Hold the goods of a confirmed shipment for the customer for 15 days, without any stock movement.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, User $user, array $attributes): ConsignmentHold
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $shipment = Shipment::query()->whereKey($attributes['shipment_id'])->lockForUpdate()->firstOrFail();

            abort_if($shipment->status !== DocumentStatus::Confirmed, 409, __('Only confirmed shipments can be held for the customer.'));
            abort_if(ConsignmentHold::query()->where('shipment_id', $shipment->id)->exists(), 409, __('The shipment already has a consignment hold.'));

            $heldFrom = CarbonImmutable::parse($attributes['held_from'] ?? $shipment->shipped_date);

            return ConsignmentHold::create([
                'team_id' => $team->id,
                'shipment_id' => $shipment->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'hold_no' => $this->documentSequence->next($team, DocumentType::ConsignmentHold, $heldFrom),
                'held_from' => $heldFrom->toDateString(),
                'held_until' => $heldFrom->addDays(15)->toDateString(),
                'status' => ConsignmentHoldStatus::Holding,
                'created_by' => $user->id,
            ]);
        });
    }
}
