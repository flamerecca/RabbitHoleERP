<?php

namespace App\Http\Resources;

use App\Models\ConsignmentHold;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConsignmentHold
 */
class ConsignmentHoldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hold_no' => $this->hold_no,
            'shipment_id' => $this->shipment_id,
            'warehouse_id' => $this->warehouse_id,
            'held_from' => $this->held_from->toDateString(),
            'held_until' => $this->held_until->toDateString(),
            'status' => $this->status->value,
            'picked_up_at' => $this->picked_up_at?->toJSON(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
