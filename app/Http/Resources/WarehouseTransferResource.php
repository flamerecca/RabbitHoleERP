<?php

namespace App\Http\Resources;

use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WarehouseTransfer
 */
class WarehouseTransferResource extends JsonResource
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
            'transfer_no' => $this->transfer_no,
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id' => $this->to_warehouse_id,
            'status' => $this->status->value,
            'transfer_date' => $this->transfer_date->toDateString(),
            'created_by' => $this->created_by,
            'items' => $this->items->map(fn (WarehouseTransferItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
