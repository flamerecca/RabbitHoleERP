<?php

namespace App\Http\Resources;

use App\Models\MaterialRequisition;
use App\Models\MaterialRequisitionItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MaterialRequisition
 */
class MaterialRequisitionResource extends JsonResource
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
            'requisition_no' => $this->requisition_no,
            'warehouse_id' => $this->warehouse_id,
            'requisition_date' => $this->requisition_date->toDateString(),
            'purpose' => $this->purpose,
            'requested_by' => $this->requested_by,
            'status' => $this->status->value,
            'created_by' => $this->created_by,
            'items' => $this->items->map(fn (MaterialRequisitionItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'note' => $item->note,
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
